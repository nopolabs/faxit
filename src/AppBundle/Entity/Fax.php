<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints\NotBlank;

class Fax
{
    /**
     * @NotBlank()
     */
    protected $numbers;

    /**
     * @NotBlank()
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