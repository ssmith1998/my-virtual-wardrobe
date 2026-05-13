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

    /**
     * Undocumented function
     *
     * @param string $prompt
     * @param string|null $season
     * @param string|null $occasion
     * @return array<string>
     */
    public function recommendOutfits(string $prompt, ?string $season = null, ?string $occasion = null): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new \InvalidArgumentException('AI prompt cannot be empty.');
        }

        $payload = [
            'prompt' => $prompt,
            'season' => $season,
            'occasion' => $occasion,
            's3Bucket' => $this->s3Bucket,
        ];

        $options = [
            'json' => $payload,
            'timeout' => 12,
        ];

        if (!empty($this->apiKey)) {
            $options['headers'] = ['x-api-key' => $this->apiKey];
        }

        $response = $this->httpClient->request('POST', $this->agentUrl, $options);
        $content = $response->getContent(false);
        $status = $response->getStatusCode();

        try {
            /** @var array<string> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('AWS AI agent returned invalid JSON: %s', $content), 0, $exception);
        }

        if ($status >= 400) {
            $message = $decoded['message'] ?? $decoded['error'] ?? sprintf('AWS AI agent returned HTTP %d', $status);
            throw new \RuntimeException($message);
        }

        return $decoded;
    }
}
