<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\AutocompleteProvider;
use App\State\GeocodeProvider;
use App\State\ReverseProvider;

/**
 * @phpstan-type PhotonFeature array{
 *     label?: string,
 *     geometry?: array{coordinates?: array{0: float, 1: float}},
 *     properties?: array{street?: string, housenumber?: string, postcode?: string, city?: string, countrycode?: string},
 * }
 */
#[ApiResource(
    shortName: 'Place',
    operations: [
        new GetCollection(
            uriTemplate: '/geocode',
            paginationEnabled: false,
            provider: GeocodeProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/autocomplete',
            paginationEnabled: false,
            provider: AutocompleteProvider::class,
        ),
        new Get(
            uriTemplate: '/reverse',
            provider: ReverseProvider::class,
        ),
    ],
)]
final class Place
{
    public function __construct(
        public string $label = '',
        public float $lat = 0.0,
        public float $lon = 0.0,
        public ?string $street = null,
        public ?string $houseNumber = null,
        public ?string $postCode = null,
        public ?string $city = null,
        public ?string $countryCode = null,
    ) {
    }

    /**
     * @param PhotonFeature $place
     */
    public static function fromArray(array $place): self
    {
        return new self(
            label: $place['label'] ?? '',
            lat: $place['geometry']['coordinates'][1] ?? 0.0,
            lon: $place['geometry']['coordinates'][0] ?? 0.0,
            street: $place['properties']['street'] ?? null,
            houseNumber: $place['properties']['housenumber'] ?? null,
            postCode: $place['properties']['postcode'] ?? null,
            city: $place['properties']['city'] ?? null,
            countryCode: $place['properties']['countrycode'] ?? null,
        );
    }
}
