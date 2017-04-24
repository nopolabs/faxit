<?php

namespace AppBundle\Service;


use AppBundle\Entity\Contact;
use AppBundle\Repository\ContactRepository;

class ContactService
{
    private $contactRepository;

    public function __construct(ContactRepository $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }

    public function getContacts() : array
    {
        return $this->contactRepository->findAll();
    }

    public function getContactByName($name) : Contact
    {
        return $this->contactRepository->findOneBy(['name' => $name]);
    }

    public function getContactById($id) : Contact
    {
        return $this->contactRepository->findOneBy(['id' => $id]);
    }
}