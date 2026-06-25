<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\Dto\Coordinate;
use App\State\RouteProvider;

/**
 * @phpstan-type OsrmRoute array{distance?: float, duration?: float, geometry?: mixed}
 */
#[ApiResource(
    shortName: 'Route',
    operations: [
        new Get(
            uriTemplate: '/route',
            provider: RouteProvider::class,
            parameters: [
                'from' => new QueryParameter(schema: ['type' => 'string'], description: 'Required. Origin as "lat,lon" or an address to geocode.'),
                'to' => new QueryParameter(schema: ['type' => 'string'], description: 'Required. Destination as "lat,lon" or an address to geocode.'),
                'lang' => new QueryParameter(schema: ['type' => 'string'], description: 'Language used when geocoding addresses (defaults to DEFAULT_LANG).'),
            ],
        ),
    ],
)]
final class Route
{
    public function __construct(
        public Coordinate $from,
        public Coordinate $to,
        public float $distanceInMeters = 0.0,
        public float $durationInSeconds = 0.0,
        public mixed $geometry = null,
    ) {
    }

    /**
     * @param OsrmRoute $route
     */
    public static function fromArray(Coordinate $from, Coordinate $to, array $route): self
    {
        return new self(
            from: $from,
            to: $to,
            distanceInMeters: $route['distance'] ?? 0.0,
            durationInSeconds: $route['duration'] ?? 0.0,
            geometry: $route['geometry'] ?? null,
        );
    }
}
