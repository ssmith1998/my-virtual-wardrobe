<?php

namespace App\Service;


use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class S3UploadService
{
    private FilesystemOperator $filesystem;

    public function __construct(
        private readonly FilesystemOperator $cdnStorage,
        private readonly string $cloudflarePublicUrl
    ) {
        $this->filesystem = $cdnStorage;
    }

    public function uploadFile(UploadedFile $file, string $folder = 'wardrobe', ?string $userId = null): string
    {
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $baseFolder = $userId ? sprintf('%s/%s', $folder, $userId) : $folder;
        $filename = sprintf('%s/%s.%s', $baseFolder, bin2hex(random_bytes(12)), $extension ?: 'bin');

        /** @var resource $stream */
        $stream = fopen($file->getRealPath(), 'rb');
        $this->filesystem->writeStream($filename, $stream, [
            'visibility' => 'public',
        ]);
        fclose($stream);

        // // Construct the public URL
        return sprintf('%s/%s', $this->cloudflarePublicUrl, $filename);
    }
}
