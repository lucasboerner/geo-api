<?php

declare(strict_types=1);

namespace App\Client;

use App\ApiResource\Place;
use App\Dto\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PhotonClient
{
    public function __construct(
        private HttpClientInterface $photonClient,
        #[Autowire(env: 'DEFAULT_LANG')]
        private string $defaultLanguage,
    ) {
    }

    /**
     * @return array<Place>
     * @throws ExceptionInterface
     */
    public function geocode(string $query, ?Coordinate $bias, int $limit): array
    {
        $parameters = ['q' => $query, 'lang' => $this->defaultLanguage, 'limit' => $limit];
        if (null !== $bias) {
            $parameters['lat'] = $bias->lat;
            $parameters['lon'] = $bias->lon;
        }

        $data = $this->photonClient
            ->request('GET', '/api', ['query' => $parameters])
            ->toArray();

        return array_map(
            fn (array $place): Place => Place::fromArray($place),
            $data['features'] ?? [],
        );
    }
}
