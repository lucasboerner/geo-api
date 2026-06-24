<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\GeocodeProvider;

#[ApiResource(
    shortName: 'Place',
    operations: [
        new GetCollection(
            uriTemplate: '/geocode',
            paginationEnabled: false,
            provider: GeocodeProvider::class,
        ),
    ],
)]
final class Place
{
    public function __construct(
        public string $label = '',
        public float $lat = 0.0,
        public float $lon = 0.0,
        public ?string $houseNumber = null,
        public ?string $postCode = null,
        public ?string $city = null,
        public ?string $countryCode = null,
    ) {
    }

    public static function fromArray(array $place): self
    {
        return new self(
            label: $place['label'] ?? '',
            lat: $place['geometry']['coordinates'][1] ?? 0.0,
            lon: $place['geometry']['coordinates'][0] ?? 0.0,
            houseNumber: $place['properties']['housenumber'] ?? null,
            postCode: $place['properties']['postcode'] ?? null,
            city: $place['properties']['city'] ?? null,
            countryCode: $place['properties']['countrycode'] ?? null,
        );
    }
}
