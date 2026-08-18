<?php
namespace App\Message;

class FileMetaDataMessage
{
    public function __construct(
        private readonly string $filename,
        private readonly string $userId,
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
