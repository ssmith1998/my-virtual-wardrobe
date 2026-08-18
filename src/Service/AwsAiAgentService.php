<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AwsAiAgentService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $agentUrl,
        private readonly ?string $apiKey,
        private readonly ?string $s3Bucket,
    ) {
    }
}
