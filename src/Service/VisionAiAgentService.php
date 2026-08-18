<?php

namespace App\Service;

use App\Entity\ClothingItem;
use Dom\Entity;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;

final class VisionAiAgentService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly S3UploadService $s3UploadService,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $visionAgentUrl,
        private readonly ?string $apiKey,
    ) {
    }

    public function recommendOutfits(string $prompt, array $clothingItems, ?string $base64Image = null): array
    {
        $prompt = sprintf(
            "You are a fashion expert. Given the following prompt, recommend clothing items from the list of clothing items. Return only JSON with an array of clothing item recommendations. Each recommendation should have a the id of the clothing item. Prompt: %s\n\nAvailable clothing items:\n%s",
            $prompt,
            $this->arrayOfItemsToString($clothingItems)
        );

        $payload = [
            'model' => 'google/gemma-4-31B-it:cerebras',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            "text" => $prompt
                        ],
                        [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:image/jpeg;base64,' . $base64Image,
                        ],
                    ],
                    ],
                ],
            ],
            'max_tokens' => 500,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->visionAgentUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 600,
                'max_duration' => 600,
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error communicating with Vision AI Agent: ' . $e->getMessage());
        }

        $response = $response->toArray();

        if(isset($response['choices'][0]['message']['content'])) {
            // Remove ```json and ```
            $content = preg_replace('/^```json\s*/i', '', $response['choices'][0]['message']['content']);
            $content = preg_replace('/\s*```$/', '', $content);
            $recommendations = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Error decoding JSON response from Vision AI Agent: ' . json_last_error_msg());
            }

            if (!isset($recommendations[0])) {
                return [];
            }

            $recommendations = $this->entityManager->getRepository(ClothingItem::class)->findBy(['id' => $recommendations]);

            if (empty($recommendations)) {
                return [];
            }

            return $recommendations;
        } else {
            throw new \RuntimeException('Invalid response from Vision AI Agent: ' . json_encode($response));
        }
    }

    public function getClothingItemMetadata(string $filename): array
    {
        $image = $this->s3UploadService->downloadFile($filename);
        $image = base64_encode($image);
        $payload = [
        'model' => 'google/gemma-4-31B-it:cerebras',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        "text" => "Analyse this clothing item. Return only JSON with category, colour, pattern, material and style."
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:image/jpeg;base64,' . $image,
                        ],
                    ],
                ],
            ],
        ],
        'max_tokens' => 100,
];

        try {
            $response = $this->httpClient->request('POST', $this->visionAgentUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 600,
            'max_duration' => 600,
        ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error communicating with Vision AI Agent: ' . $e->getMessage());
        }

        $response = $response->toArray();

        if(isset($response['choices'][0]['message']['content'])) {
            // Remove ```json and ```
            $content = preg_replace('/^```json\s*/i', '', $response['choices'][0]['message']['content']);
            $content = preg_replace('/\s*```$/', '', $content);
            $metadata = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Error decoding JSON response from Vision AI Agent: ' . json_last_error_msg());
            }
            return $metadata;
        } else {
            throw new \RuntimeException('Invalid response from Vision AI Agent: ' . json_encode($response));
        }
    }

    public function arrayOfItemsToString(array $items): string
    {
        $itemDescriptions = array_map(function ($item) {
            return sprintf(
                "id: %s, metadata: %s",
                $item->getId() ?? 'Unknown',
                implode(', ', $item->getMetadata()) ?? 'Unknown',
            );
        }, $items);

        return implode("\n", $itemDescriptions);
    }

}
