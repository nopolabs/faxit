<?php

namespace AppBundle\Entity;


use Doctrine\ORM\EntityRepository;

class FaxRepository extends EntityRepository
{
    public function persist(Fax $fax)
    {
        $this->_em->persist($fax);
    }

    public function findOneByFid($fid) : Fax
    {
        return $this->findOneBy(['fid' => $fid]);
    }
}
