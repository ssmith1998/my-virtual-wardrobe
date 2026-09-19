<?php

namespace App\Service;

use App\Entity\ClothingItem;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class VisionAiAgentService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly S3UploadService $s3UploadService,
        private readonly EntityManagerInterface $entityManager,
        private readonly WeatherService $weatherService,
        private readonly GoogleService $googleCalendarService,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly string $visionAgentUrl,
        private readonly ?string $apiKey,
        private readonly string $model
    ) {
    }
    /**
     * Get recommendations
     *
     * @param string $prompt
     * @param array $clothingItems
     * @param array|null $base64Images
     * @return array
     */
    public function recommendOutfits(string $prompt, array $clothingItems, ?array $base64Images = []): array
    {

        $currentWeather = $this->weatherService->getWeather();
        $this->logger->info('Current weather: {weather}', ['weather' => json_encode($currentWeather)]);

        try {
            $googleCalendarEvents = $this->googleCalendarService->getGoogleCalendarEvents($this->security->getUser());

        } catch( \Exception $e) {
            $this->logger->error('Error fetching calendar events: {error}', ['error' => $e->getMessage()]);
            return [
                'error' => 'Error fetching calendar events: ' . $e->getMessage()
            ];
        }


        $this->logger->info('Current Google Calendar events: {events}', ['events' => json_encode($googleCalendarEvents)]);

        $prompt = sprintf(
            "You are a fashion expert. Given the following prompt, recommend clothing items from the list of clothing items.
            If there are calendar events, consider them when making recommendations.
            Try and give full outfits if possible.
            Return only JSON with an array of clothing item recommendations. 
            Each recommendation should have a the id of the clothing item and a brief explanation of why it was recommended.
             Prompt: %s\n\nAvailable clothing items:\n%s\n\nCurrent weather: %s, %s°C, Humidity: %s%%\n\nCurrent Google Calendar Events:\n%s",
            $prompt,
            $this->arrayOfItemsToString($clothingItems),
            $currentWeather['condition'],
            $currentWeather['temperature'],
            $currentWeather['humidity'],
            $this->googleCalendarService->eventsToString($googleCalendarEvents)
        );

        $promptImages = [];

        foreach ($base64Images as $base64Image) {
            $promptImages[] = $this->createImageInputPart($base64Image, 'image/jpeg');
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                        
                        ...$promptImages 
            ],
        ];

        if(!$base64Images) {
            $payload = [
                'model' => $this->model,
                'input' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                ],
            ];
        }

        $this->logger->info('Sending request to Vision AI Agent with payload: {payload}', ['payload' => json_encode($payload)]);
        

        try {
            $response = $this->httpClient->request('POST', $this->visionAgentUrl, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error communicating with Vision AI Agent: ' . $e->getMessage());
            return [
                'error' => 'Error communicating with Vision AI Agent: ' . $e->getMessage()
            ];
        }

        $response = $response->toArray();

        $this->logger->info('Response content from gemini: {response}', ['response' => json_encode($response['steps'][1]['content'][0]['text'] ?? 'key does not exist')]);

        if(isset($response['steps'][1]['content'][0]['text'])) {
            // Remove ```json and ```
            $content = preg_replace('/^```json\s*/i', '', $response['steps'][1]['content'][0]['text']);
            $content = preg_replace('/\s*```$/', '', $content);
            $this->logger->info('Cleaned content from Vision AI Agent: {content}', ['content' => $content]);
            $recommendations = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Error decoding JSON response from Vision AI Agent: ' . json_last_error_msg());
            }

            $this->logger->info('Decoded recommendations from Vision AI Agent: {recommendations}', ['recommendations' => json_encode($recommendations)]);

            if (!isset($recommendations[0])) {
                return [];
            }

            $recommendations = array_map(function($item) {
                return $item['id'] ?? null;
            }, $recommendations);

            $this->logger->info('Extracted clothing item IDs from recommendations: {recommendationIds}', ['recommendationIds' => json_encode($recommendations)]);

            $recommendations = $this->entityManager->getRepository(ClothingItem::class)->findBy(['id' => $recommendations]);

            if (empty($recommendations)) {
                return [];
            }

            return $recommendations;
        } else {
            $this->logger->error('Invalid response from Vision AI Agent: ' . json_encode($response));
            return [
                'error' => 'Invalid response from Vision AI Agent: ' . json_encode($response)
            ];
        }
    }

    public function getClothingItemMetadata(string $filename): array
    {
        $image = $this->s3UploadService->downloadFile($filename);
        $image = base64_encode($image);
        $payload = [
        'model' => $this->model,
        'input' => [
            [
                'type' => 'text',
                'text' => "Analyse this clothing item. Return only JSON with category, colour, pattern, material and style."
            ],
            $this->createImageInputPart($image, 'image/jpeg'),
        ],

        ];

        try {
          $response = $this->httpClient->request('POST', $this->visionAgentUrl, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error communicating with Vision AI Agent: ' . $e->getMessage());
            return [
                'error' => 'Error communicating with Vision AI Agent: ' . $e->getMessage()
            ];
        }

        $this->logger->info('Response content from gemini: {response}', ['response' => json_encode($response->getContent(false))]);

        $response = $response->toArray();
        $this->logger->info('Response content from gemini: {response}', ['response' => json_encode($response['steps'][1]['content'][0]['text'] ?? 'key does not exist')]);


        if(isset($response['steps'][1]['content'][0]['text'])) {
            // Remove ```json and ```
            $content = preg_replace('/^```json\s*/i', '', $response['steps'][1]['content'][0]['text']);
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

    /**
     * converts clothing items to a string
     *
     * @param array $items
     * @return string
     */
    private function createImageInputPart(string $base64Image, string $mimeType): array
    {
        return [
            'type' => 'image',
            'mime_type' => $mimeType,
            'data' => $base64Image,
        ];
    }

    private function arrayOfItemsToString(array $items): string
    {
        $itemDescriptions = array_map(function ($item) {
            return sprintf(
                "id: %s, metadata: %s",
                $item->getId() ?? 'Unknown',
                $this->getMetaDataItemsAsString($item->getMetadata() ?? []),
            );
        }, $items);

        return implode("\n", $itemDescriptions);
    }

    /**
     * get clothing item meta asa a string
     *
     * @param array $metadata
     * @return string
     */
    private function getMetaDataItemsAsString(array $metadata): string
    {
        $metadataStrings = [];
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $metadataStrings[] = sprintf("%s: %s", $key, $value);
        }
        return implode(", ", $metadataStrings);
    }

}
