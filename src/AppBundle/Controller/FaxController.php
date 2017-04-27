<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Fax;
use AppBundle\Service\ContactService;
use AppBundle\Service\FaxService;
use Doctrine\ORM\EntityManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FaxController extends Controller
{
    public function indexAction(Request $request): Response
    {
        $form = $this->getFaxForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $recipients = $data['recipients'];
            $text = $data['text'];

            if ($form->get('previewHtml')->isClicked()) {
                $contact = $this->getContactService()->getContactById($recipients[0]);
                $html = $this->renderFaxHtml($contact, $text);

                return new Response($html);
            }

            if ($form->get('previewPdf')->isClicked()) {
                $contact = $this->getContactService()->getContactById($recipients[0]);
                $pdf = $this->renderFaxPdf($contact, $text);

                return new Response($pdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="fax.pdf"',
                ]);
            }

            if ($form->get('sendFax')->isClicked()) {
                $contacts = $this->getContactService()->getContactsById($recipients);
                $this->sendFaxes($contacts, $text);
            }
        }

        return $this->render('fax/form.html.twig', [
            'incoming_url' => $this->generatePublicUrl('/incoming'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * This serves the pdf content for outgoing faxes.
     * @throws \InvalidArgumentException
     */
    public function pdfAction($fid) : Response
    {
        $pdf = $this->getFaxService()->getPdf($fid);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s.pdf"', $key),
        ]);
    }

    /**
     * This accepts status updates for outgoing faxes.
     */
    public function statusAction(Request $request, $fid) : Response
    {
        $status = $request->request->get('FaxStatus');

        $this->getFaxService()->updateStatus($fid, $status);

        return new Response();
    }

    /**
     * This serves the TwiML for incoming faxes.
     * The inbound twilio phone number needs to be configured with the url for this handler.
     * @throws \InvalidArgumentException
     */
    public function incomingAction() : Response
    {
        $twiml = new SimpleXMLElement("<Response></Response>");
        $receiveEl = $twiml->addChild('Receive');
        $receiveEl->addAttribute('action', '/receive');
        $xml = $twiml->asXML();

        return new Response($xml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Twilio will post pdf of incoming fax here as specified by TwiML from incomingAction()
     * @see https://www.twilio.com/docs/api/fax/receive#element-attributes
     */
    public function receiveAction(Request $request) : Response
    {
        $sid = $request->request->get("FaxSid");
        $status = $request->request->get("FaxStatus");
        $faxNumber = $request->request->get("From");
        $url = $request->request->get("MediaUrl");

        $client = new Client();
        $response = $client->get($url);
        $pdf = $response->getBody();

        $fax = $this->getFaxService()->receiveFax($sid, $status, $faxNumber, $pdf);

        $this->getLog()->info("received fax: {$fax->getFid()}");

        return new Response($url);
    }

    protected function sendFaxes(array $contacts, string $text)
    {
        foreach ($contacts as $contact) {
            $fax = $this->prepareFax($contact, $text);
            $fax = $this->sendFax($fax);
            $this->save($fax);
            $this->addFlash('notice', "Fax sent to {$contact->getName()} ({$fax->getSid()})");
        }
    }

    protected function prepareFax(Contact $contact, string $text) : Fax
    {
        $faxNumber = $contact->getFax();
        $pdf = $this->renderFaxPdf($contact, $text);

        return $this->getFaxService()->prepareFax($faxNumber, $pdf);
    }

    protected function sendFax(Fax $fax) : Fax
    {
        $pdfUrl = $this->getPdfUrl($fax->getFid());
        $statusUrl = $this->getStatusUrl($fax->getFid());
        $faxNumber = $fax->getFaxNumber();

        return $this->getFaxService()->sendFax($pdfUrl, $faxNumber, $statusUrl);
    }

    protected function getPdfUrl($fid)
    {
        $path = $this->generateUrl('fax_pdf', ['fid' => $fid]);

        return $this->generatePublicUrl($path);
    }

    protected function getStatusUrl($fid)
    {
        $path = $this->generateUrl('fax_status', ['fid' => $fid]);

        return $this->generatePublicUrl($path);
    }

    protected function save(Fax $fax)
    {
        $this->getEntityManager()->flush($fax);
    }

    protected function renderFaxHtml(Contact $contact, string $text)
    {
        $params = [
            'contact' => $contact,
            'text' => $text,
            'signature' => 'Best Regards,',
            'return' => [
                'name' => 'Dan Revel',
                'addr' => 'General Delivery',
                'city' => 'Portland',
                'state' => 'OR',
                'zip' => '97217',
            ],
        ];

        return $this->getTwig()->render('fax/template.html.twig', $params);
    }

    protected function renderFaxPdf(Contact $contact, string $text)
    {
        $html = $this->renderFaxHtml($contact, $text);

        return $this->htmlToPdf($html);
    }

    protected function getFaxForm() : FormInterface
    {
        $builder = $this->createFormBuilder();
        $builder->add(
            'recipients',
            ChoiceType::class,
            [
                'placeholder' => 'Choose a recipient',
                'choices' => $this->getFaxChoices(),
                'multiple' => true,
                'expanded' => true,
                'label' => false,
            ]
        );
        $builder->add('text', TextareaType::class, ['label' => false]);
        $builder->add('previewHtml', SubmitType::class, ['label' => 'Preview HTML']);
        $builder->add('previewPdf', SubmitType::class, ['label' => 'Preview PDF']);
        $builder->add('sendFax', SubmitType::class, ['label' => 'Send Fax']);
        $form = $builder->getForm();
        return $form;
    }

    protected function htmlToPdf($html, Options $options = null)
    {
        $dompdf = $this->newDompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    protected function newDompdf(Options $options = null) : Dompdf
    {
        if ($options === null) {
            $options = new Options();
            $options->set('defaultFont', 'Times New Roman');
        }

        return new Dompdf($options);
    }

    protected function getFaxChoices() : array
    {
        $contacts = $this->getContactService()->getContacts();

        $choices = [];
        foreach ($contacts as $contact) {
            /** @var Contact $contact */
            $choices[$contact->getName()] = $contact->getId();
        }

        return $choices;
    }

    protected function generatePublicUrl($path)
    {
        return $this->container->getParameter('public_url') . $path;
    }

    protected function getLog() : LoggerInterface
    {
        return $this->container->get('logger');
    }

    protected function getFaxService() : FaxService
    {
        return $this->container->get('fax_service');
    }

    protected function getContactService() : ContactService
    {
        return $this->container->get('contact_service');
    }

    protected function getTwig() : TwigEngine
    {
        return $this->container->get('templating');
    }

    protected function getEntityManager() : EntityManager
    {
        return $this->container->get('doctrine.orm.default_entity_manager');
    }

    private function getEventDispatcher() : EventDispatcherInterface
    {
        return $this->container->get('event_dispatcher');
    }
}
