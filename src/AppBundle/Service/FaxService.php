<?php

namespace AppBundle\Service;


use AppBundle\Entity\Fax;
use AppBundle\Entity\FaxRepository;
use Twilio\Rest\Client;

class FaxService
{
    private $sid;
    private $token;
    private $phoneNumber;
    private $storageService;
    private $faxRepository;

    public function __construct(
        string $sid,
        string $token,
        string $phoneNumber,
        StorageService $storageService,
        FaxRepository $faxRepository)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->phoneNumber = $phoneNumber;
        $this->storageService = $storageService;
        $this->faxRepository = $faxRepository;
    }

    public function prepareFax($faxNumber, $pdf) : Fax
    {
        $fid = $this->storageService->create($pdf);

        $fax = new Fax($fid, $faxNumber);

        $this->faxRepository->persist($fax);

        return $fax;
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

    public function getPdf($fid) : string
    {
        return $this->storageService->read($fid);
    }

    public function updateStatus($fid, $status) : Fax
    {
        $fax = $this->faxRepository->findOneByFid($fid);
        $fax->setStatus($status);

        return $fax;
    }

    public function receiveFax($sid, $status, $faxNumber, $pdf) : Fax
    {
        $fid = $this->storageService->create($pdf);

        $fax = new Fax($fid, $faxNumber);
        $fax->setSid($sid);
        $fax->setStatus($status);

        $this->faxRepository->persist($fax);

        return $fax;
    }
}