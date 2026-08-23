<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;   

final class WeatherService
{
    public function __construct(
            private readonly HttpClientInterface $httpClient,
            private readonly string $apiKey
        ) {
        }
    /**
     * gets current weather data from the OpenWeather API
     *
     * @return array{temperature: float, condition: string, humidity: int}
     */
    public function getWeather(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
            'query' => [
                'q' => 'Leeds,UK',
                'appid' => $this->apiKey,
                'units' => 'metric',
            ],
        ]);

        return [
            'temperature' => $response->toArray()['main']['temp'],
            'condition' => $response->toArray()['weather'][0]['main'],
            'humidity' => $response->toArray()['main']['humidity'],
        ];
    }
}