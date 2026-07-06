<?php

namespace App\Service\External;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FacilityWeatherService
{
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly float $defaultLatitude,
        private readonly float $defaultLongitude,
    ) {
    }

    public function getCurrentWeather(?float $latitude = null, ?float $longitude = null): FacilityWeather
    {
        try {
            $response = $this->httpClient->request('GET', self::FORECAST_URL, [
                'query' => [
                    'latitude' => $latitude ?? $this->defaultLatitude,
                    'longitude' => $longitude ?? $this->defaultLongitude,
                    'current_weather' => 'true',
                    'timezone' => 'Europe/Paris',
                ],
                'timeout' => 3,
            ]);

            if ($response->getStatusCode() >= 400) {
                return FacilityWeather::unavailable();
            }

            return $this->hydrateWeather($response->toArray(false));
        } catch (ExceptionInterface) {
            return FacilityWeather::unavailable();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateWeather(array $payload): FacilityWeather
    {
        $currentWeather = $payload['current_weather'] ?? null;
        if (!is_array($currentWeather)) {
            return FacilityWeather::unavailable();
        }

        $temperature = $currentWeather['temperature'] ?? null;
        $windSpeed = $currentWeather['windspeed'] ?? null;

        if (!is_numeric($temperature) || !is_numeric($windSpeed)) {
            return FacilityWeather::unavailable();
        }

        return new FacilityWeather(
            (float) $temperature,
            (float) $windSpeed,
            sprintf('%.1f degC, vent %.1f km/h', (float) $temperature, (float) $windSpeed),
            true,
        );
    }
}
