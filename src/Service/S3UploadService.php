<?php

namespace App\Service;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class S3UploadService
{
    private readonly Filesystem $filesystem;
    private readonly string $bucket;

    public function __construct(
        private readonly string $region,
        string $bucket,
        private readonly ?string $accessKey = null,
        private readonly ?string $secretKey = null,
    ) {
        $this->bucket = $bucket;
        $client = new S3Client([
            'credentials' => [
                'key' => $this->accessKey,
                'secret' => $this->secretKey,
            ],
            'region' => $this->region,
            'version' => 'latest',
        ]);

        $adapter = new AwsS3V3Adapter($client, $bucket);
        $this->filesystem = new Filesystem($adapter);
    }

    public function uploadFile(UploadedFile $file, string $folder = 'wardrobe', ?int $userId = null): string
    {
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $baseFolder = $userId ? sprintf('user-%d/%s', $userId, trim($folder, '/')) : trim($folder, '/');
        $filename = sprintf('%s/%s.%s', $baseFolder, bin2hex(random_bytes(12)), $extension ?: 'bin');

        $stream = fopen($file->getRealPath(), 'rb');
        $this->filesystem->writeStream($filename, $stream, [
            'visibility' => 'public',
        ]);
        fclose($stream);

        // Construct the public URL
        return sprintf('https://%s.s3.%s.amazonaws.com/%s', $this->bucket, $this->region, $filename);
    }
}
