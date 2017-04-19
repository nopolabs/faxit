<?php

namespace AppBundle\Service;


use DateTime;
use Twilio\Rest\Client;

class FaxService
{
    private $pdfDir;
    private $sid;
    private $token;
    private $phoneNumber;

    public function __construct($pdfDir, $sid, $token, $phoneNumber)
    {
        $this->pdfDir = $pdfDir;
        $this->sid = $sid;
        $this->token = $token;
        $this->phoneNumber = $phoneNumber;

        if (!is_dir($pdfDir)) {
            mkdir($pdfDir);
        }
    }

    public function putPdf($pdf)
    {
        $now = new DateTime();
        $name = 'fax-' . $now->format('Y-m-d-H-i-s') . '.pdf';
        file_put_contents($this->pdfDir . '/' . $name, $pdf);
        return $name;
    }

    public function getPdf($name)
    {
        return file_get_contents($this->pdfDir . '/' . $name);
    }

    public function sendFax($pdfUrl, $faxNumber)
    {
        $client = new Client($this->sid, $this->token);
        $fax = $client->fax->v1->faxes->create(
            $this->phoneNumber,
            $faxNumber,
            $pdfUrl
        );

        return $fax->sid;
    }
}