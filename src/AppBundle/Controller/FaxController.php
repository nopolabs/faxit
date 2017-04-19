<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Fax;
use AppBundle\Service\FaxService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FaxController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $fax = new Fax();

        $builder = $this->createFormBuilder($fax);
        $builder->add('number', ChoiceType::class, [
            'placeholder' => 'Choose a recipient',
            'choices' => $this->getFaxChoices(),
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
                'text' => $fax->getText(),
            ];

            $html = $this->getTwig()->render('fax/template.html.twig', $params);

            if ($form->get('previewHtml')->isClicked()) {
                return new Response($html);
            } else {
                $pdf = $this->htmlToPdf($html);
                if ($form->get('previewPdf')->isClicked()) {
                    return new Response($pdf, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="fax.pdf"',
                    ]);
                } elseif ($form->get('sendFax')->isClicked()) {
                    $name = $this->getFaxService()->putPdf($pdf);
                    $url = $this->generateUrl('pdf', ['name' => $name]);
                    $sid = $this->getFaxService()->sendFax($url, $fax->getNumber());
                    $this->addFlash('notice', "Your fax was sent! ($sid)");
                };
            }
        }

        return $this->render('fax/form.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/pdf/{name}", name="pdf")
     */
    public function pdfAction($name)
    {
        $pdf = $this->getFaxService()->getPdf($name);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $name),
        ]);
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
            $options->set('defaultFont', 'Courier');
        }

        return new Dompdf($options);
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
        return $this->container->getParameter('fax_choices');
    }
}
