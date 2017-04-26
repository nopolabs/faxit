<?php

namespace AppBundle\Service;


class PdfStorageService
{
    private $dir;

    public function __construct(string $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $this->dir = $dir;
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
        return bin2hex(random_bytes(20));
    }
}