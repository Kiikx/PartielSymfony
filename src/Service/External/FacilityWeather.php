<?php

namespace App\Service\External;

final readonly class FacilityWeather
{
    public function __construct(
        public ?float $temperature,
        public ?float $windSpeed,
        public string $summary,
        public bool $available,
    ) {
    }

    public static function unavailable(string $summary = 'Information meteo indisponible'): self
    {
        return new self(null, null, $summary, false);
    }
}
