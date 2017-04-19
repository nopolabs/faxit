<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Fax;
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
        $fax->setNumbers([]);
        $fax->setText('fan mail from a flounder');

        $form = $this->createFormBuilder($fax)
            ->add('numbers', ChoiceType::class, array(
                'choices' => array(
                    'Ron Wyden' => '123',
                    'Jeff Merkley' => '456',
                    'Earl Blumenauer' => '789',
                ),
                'multiple' => 'true',
                'expanded' => 'true',
            ))
            ->add('text', TextareaType::class)
            ->add('html', SubmitType::class, array('label' => 'Preview HTML'))
            ->add('pdf', SubmitType::class, array('label' => 'Preview PDF'))
            ->add('fax', SubmitType::class, array('label' => 'Send Fax'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Fax $fax */
            $fax = $form->getData();

            $params = [
                'text' => $fax->getText(),
            ];

            $html = $this->getTwig()->render('fax/template.html.twig', $params);

            if ($form->get('html')->isClicked()) {
                return new Response($html);
            } elseif ($form->get('pdf')->isClicked()) {
                $pdf = $this->htmlToPdf($html);
                return new Response($pdf, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="fax.pdf"',
                ]);
            } elseif ($form->get('fax')->isClicked()) {
                $view = 'FAX';
            };

            return $this->render('fax/template.html.twig', array(
                'text' => $view . $fax->getText(),
            ));
        }

        return $this->render('fax/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function htmlToPdf($html, Options $options = null)
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


    protected function getTwig() : TwigEngine
    {
        return $this->container->get('templating');
    }
}
