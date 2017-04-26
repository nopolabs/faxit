<?php

namespace AppBundle\Service;


class StorageService
{
    private $dir;
    private $idService;

    public function __construct(string $dir, IdService $idService)
    {
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $this->dir = $dir;
        $this->idService = $idService;
    }

    public function create(string $pdf): string
    {
        do {
            $key = $this->generateKey();
            $path = $this->keyToPath($key);
        } while (file_exists($path));

        file_put_contents($path, $pdf);

        return $key;
    }

    public function read(string $key): string
    {
        $path = $this->keyToPath($key);

        return file_get_contents($path);
    }

    protected function keyToPath(string $key) : string
    {
        return $this->dir . '/' . $key . '.pdf';
    }

    protected function generateKey(): string
    {
        return $this->idService->getId();
    }
}