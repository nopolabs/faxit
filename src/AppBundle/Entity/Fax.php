<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints\NotBlank;

class Fax
{
    /**
     * @NotBlank()
     */
    protected $number = '';

    /**
     * @NotBlank()
     */
    protected $text = '';

    public function getNumber() : string
    {
        return $this->number;
    }

    public function setNumber(string $number)
    {
        $this->number = $number;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }
}