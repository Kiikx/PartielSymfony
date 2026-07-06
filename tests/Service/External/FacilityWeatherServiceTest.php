<?php

namespace App\Tests\Service\External;

use App\Service\External\FacilityWeatherService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FacilityWeatherServiceTest extends TestCase
{
    public function testReturnsWeatherFromExternalApiPayload(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'current_weather' => [
                'temperature' => 18.4,
                'windspeed' => 12.7,
            ],
        ], JSON_THROW_ON_ERROR)));

        $weather = (new FacilityWeatherService($httpClient, 48.8566, 2.3522))->getCurrentWeather();

        self::assertTrue($weather->available);
        self::assertSame(18.4, $weather->temperature);
        self::assertSame(12.7, $weather->windSpeed);
        self::assertSame('18.4 degC, vent 12.7 km/h', $weather->summary);
    }

    public function testReturnsFallbackWhenApiPayloadIsInvalid(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{}'));

        $weather = (new FacilityWeatherService($httpClient, 48.8566, 2.3522))->getCurrentWeather();

        self::assertFalse($weather->available);
        self::assertNull($weather->temperature);
        self::assertSame('Information meteo indisponible', $weather->summary);
    }

    public function testReturnsFallbackWhenRequestFails(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            return new MockResponse([new TransportException('Timeout')]);
        });

        $weather = (new FacilityWeatherService($httpClient, 48.8566, 2.3522))->getCurrentWeather();

        self::assertFalse($weather->available);
        self::assertSame('Information meteo indisponible', $weather->summary);
    }
}
