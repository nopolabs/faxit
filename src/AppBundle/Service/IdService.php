<?php

namespace AppBundle\Service;


class IdService
{
    public function getId() : string
    {
        return bin2hex(random_bytes(20));
    }
}