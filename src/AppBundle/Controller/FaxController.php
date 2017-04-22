<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Fax;
use AppBundle\Service\FaxService;
use Dompdf\Dompdf;
use Dompdf\Options;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FaxController extends Controller
{
    public function indexAction(Request $request): Response
    {
        $fax = new Fax();

        $faxChoices = $this->getFaxChoices();

        $builder = $this->createFormBuilder($fax);
        $builder->add('number', ChoiceType::class, [
            'placeholder' => 'Choose a recipient',
            'choices' => $faxChoices,
        ]);
        $builder->add('text', TextareaType::class);
        $builder->add('previewHtml', SubmitType::class, ['label' => 'Preview HTML']);
        $builder->add('previewPdf', SubmitType::class, ['label' => 'Preview PDF']);
        $builder->add('sendFax', SubmitType::class, ['label' => 'Send Fax']);
        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Fax $fax */
            $fax = $form->getData();

            $params = [
                'address' => [
                    'name' => 'The Honorable Ron Wyden',
                    'addr' => '221 Dirksen Senate Office Bldg.',
                    'addr2' => '',
                    'city' => 'Washington',
                    'state' => 'D.C.',
                    'zip' => '20510',
                ],
                'salutation' => 'To the Honorable Ron Wyden,',
                'text' => $fax->getText(),
                'signature' => 'Best Regards,',
                'return' => [
                    'name' => 'Dan Revel',
                    'addr' => 'General Delivery',
                    'city' => 'Portland',
                    'state' => 'OR',
                    'zip' => '97217',
                ],
            ];

            $html = $this->getTwig()->render('fax/template.html.twig', $params);

            if ($form->get('previewHtml')->isClicked()) {
                return new Response($html);
            }

            $pdf = $this->htmlToPdf($html);

            if ($form->get('previewPdf')->isClicked()) {
                return new Response($pdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="fax.pdf"',
                ]);
            }

            if ($form->get('sendFax')->isClicked()) {
                $name = $this->getFaxService()->putPdf($pdf);
                $url = $this->generatePublicUrl('/pdf/' . $name);
                $sid = $this->getFaxService()->sendFax($url, $fax->getNumber());
                $this->addFlash('notice', "Your fax was sent! ($sid)");
            }
        }

        return $this->render('fax/form.html.twig', [
            'incoming_number' => $faxChoices['Test'],
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

    protected function getLog() : LoggerInterface
    {
        return $this->container->get('logger');
    }

    protected function getFaxService() : FaxService
    {
        return $this->container->get('fax_service');
    }

    protected function getTwig() : TwigEngine
    {
        return $this->container->get('templating');
    }

    private function getFaxChoices() : array
    {
        $contacts = $this->container->getParameter('contacts');

        $choices = [];
        foreach ($contacts as $key => $contact) {
            $choices[$key] = $contact['fax'];
        }

        return $choices;
    }

    private function generatePublicUrl($path)
    {
        return $this->container->getParameter('public_url') . $path;
    }
}
