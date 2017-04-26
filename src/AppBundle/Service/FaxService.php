<?php

namespace AppBundle\Service;


use AppBundle\Entity\Fax;
use Twilio\Rest\Client;

class FaxService
{
    private $sid;
    private $token;
    private $phoneNumber;
    private $storageService;

    public function __construct($sid, $token, $phoneNumber, StorageService $storageService)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->phoneNumber = $phoneNumber;
        $this->storageService = $storageService;
    }

    public function prepareFax($faxNumber, $pdf) : Fax
    {
        $key = $this->storageService->create($pdf);

        return new Fax($key, $faxNumber);
    }

    public function sendFax(Fax $fax, string $pdfUrl, string $statusUrl) : Fax
    {
        $client = new Client($this->sid, $this->token);

        $faxInstance = $client->fax->v1->faxes->create(
            $this->phoneNumber,
            $fax->getFaxNumber(),
            $pdfUrl,
            ['statusCallback' => $statusUrl]
        );

        $fax->setSid($faxInstance->sid);
        $fax->setStatus($faxInstance->status);

        return $fax;
    }
}