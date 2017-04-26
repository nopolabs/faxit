<?php

namespace AppBundle\Service;


use AppBundle\Entity\Fax;
use Twilio\Rest\Client;

class FaxService
{
    private $sid;
    private $token;
    private $phoneNumber;

    public function __construct($sid, $token, $phoneNumber)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->phoneNumber = $phoneNumber;
    }

    public function sendFax($pdfUrl, $faxNumber) : Fax
    {
        $client = new Client($this->sid, $this->token);
        $fax = $client->fax->v1->faxes->create(
            $this->phoneNumber,
            $faxNumber,
            $pdfUrl
        );

        return new Fax($fax->sid, $fax->status);
    }
}