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
     * @var User
     *
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

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

    public function __construct(string $sid, string $status)
    {
        $this->user = null;
        $this->sid = $sid;
        $this->status = $status;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function getSid() : string
    {
        return $this->sid;
    }

    public function getStatus() : string
    {
        return $this->status;
    }
}