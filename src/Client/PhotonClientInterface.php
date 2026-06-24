<?php

declare(strict_types=1);

namespace App\Client;

use App\ApiResource\Place;
use App\Dto\Coordinate;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

interface PhotonClientInterface
{
    /**
     * @return array<Place>
     *
     * @throws ExceptionInterface
     */
    public function geocode(string $query, ?Coordinate $bias, int $limit): array;

    /**
     * @throws ExceptionInterface
     */
    public function reverse(float $lat, float $lon, string $lang): ?Place;
}
