<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use AppBundle\Event\FaxCreatedEvent;
use AppBundle\Service\ContactService;
use AppBundle\Service\FaxService;
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
                $this->generateFaxes($contacts, $text);
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
    public function pdfAction($name) : Response
    {
        $pdf = $this->getFaxService()->getPdf($name);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $name),
        ]);
    }

    /**
     * This service the TwiML for incoming faxes.
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
     */
    public function receiveAction(Request $request) : Response
    {
        $url = $request->request->get("MediaUrl");

        $client = new Client();
        $response = $client->get($url);
        $pdf = $response->getBody();

        $name = $this->getFaxService()->putPdf($pdf, 'in');

        $this->getLog()->info("received fax: $name");

        return new Response($url);
    }

    protected function generateFaxes(array $contacts, string $text)
    {
        foreach ($contacts as $contact) {
            /** @var Contact $contact */
            $pdf = $this->renderFaxPdf($contact, $text);
            $pdfName = $this->getFaxService()->putPdf($pdf);
            $fax = $contact->getFax();
            $name = $contact->getName();
            // TODO save a fax record

            $event = new FaxCreatedEvent();
            $this->getEventDispatcher()->dispatch($event);

            // TODO move this to a fax ready listener
            $url = $this->generatePublicUrl('/pdf/' . $pdfName);
            $sid = $this->getFaxService()->sendFax($url, $fax);
            $this->addFlash('notice', "Fax prepared for $name ($sid)");
        }
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

    private function getEventDispatcher() : EventDispatcherInterface
    {
        return $this->container->get('event_dispatcher');
    }
}
