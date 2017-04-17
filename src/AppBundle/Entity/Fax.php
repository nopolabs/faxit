<?php

namespace AppBundle\Entity;


class Fax
{
    protected $number;
    protected $text;

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