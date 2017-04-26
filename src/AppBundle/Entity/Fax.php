<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fax
 *
 * @ORM\Table(name="faxes")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FaxRepository")
 */
class Fax
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="fid", type="string", length=40)
     */
    private $fid;

    /**
     * @var string
     *
     * @ORM\Column(name="fax_number", type="string", length=40)
     */
    private $faxNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="sid", type="string", length=40)
     */
    private $sid;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    private $status;

    public function __construct(string $fid, string $faxNumber)
    {
        $this->fid = $fid;
        $this->faxNumber = $faxNumber;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFid(): string
    {
        return $this->fid;
    }

    /**
     * @return string
     */
    public function getFaxNumber(): string
    {
        return $this->faxNumber;
    }

    /**
     * @return string
     */
    public function getSid(): string
    {
        return $this->sid;
    }

    /**
     * @param string $sid
     */
    public function setSid(string $sid)
    {
        $this->sid = $sid;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }
}