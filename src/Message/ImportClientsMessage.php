<?php

namespace App\Message;

readonly class ImportClientsMessage
{
    public function __construct(
        private string $filePath,
        private string $fileName,
    ) {
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
}
