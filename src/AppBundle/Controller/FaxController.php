<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Fax;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

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
            ->add('save', SubmitType::class, array('label' => 'Send Fax'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Fax $fax */
            $fax = $form->getData();

            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($fax);
            // $em->flush();

            return $this->render('fax/template.html.twig', array(
                'text' => $fax->getText(),
            ));
        }

        return $this->render('fax/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
