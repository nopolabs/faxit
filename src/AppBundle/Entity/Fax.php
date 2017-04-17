<?php

namespace AppBundle\Entity;


class Fax
{
    /**
     * @Assert\NotBlank()
     */
    protected $numbers;

    /**
     * @Assert\NotBlank()
     */
    protected $text;

    public function getNumbers() : array
    {
        return $this->numbers;
    }

    public function setNumbers(array $numbers)
    {
        $this->numbers = $numbers;
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