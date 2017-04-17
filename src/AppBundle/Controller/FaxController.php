<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Fax;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class FaxController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $task = new Fax();
        $task->setNumber('503-555-1212');
        $task->setText('fan mail from a flounder');

        $form = $this->createFormBuilder($task)
            ->add('number', TextType::class)
            ->add('text', TextareaType::class)
            ->add('save', SubmitType::class, array('label' => 'Send Fax'))
            ->getForm();

        return $this->render('fax/index.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
