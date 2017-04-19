<?php

namespace AppBundle\Service;


use DateTime;

class FaxService
{
    private $faxDir;
    private $sid;
    private $token;
    private $phoneNumber;

    public function __construct($faxDir, $sid, $token, $phoneNumber)
    {
        $this->faxDir = $faxDir;
        $this->sid = $sid;
        $this->token = $token;
        $this->phoneNumber = $phoneNumber;
    }

    public function putPdf($pdf)
    {
        $now = new DateTime();
        $name = 'fax-' . $now->format('Y-m-d-H-i-s') . '.pdf';
        file_put_contents($this->faxDir . '/' . $name, $pdf);
        return $name;
    }

    public function getPdf($name)
    {
        return file_get_contents($this->faxDir . '/' . $name);
    }
}