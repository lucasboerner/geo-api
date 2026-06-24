<?php

declare(strict_types=1);

namespace App\Client;

use App\ApiResource\Place;
use App\Dto\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-import-type PhotonFeature from Place
 */
final readonly class PhotonClient implements PhotonClientInterface
{
    public function __construct(
        private HttpClientInterface $photonClient,
        #[Autowire(env: 'DEFAULT_LANG')]
        private string $defaultLanguage,
    ) {
    }

    /**
     * @return array<Place>
     *
     * @throws ExceptionInterface
     */
    public function geocode(string $query, ?Coordinate $bias, int $limit): array
    {
        $parameters = ['q' => $query, 'lang' => $this->defaultLanguage, 'limit' => $limit];
        if (null !== $bias) {
            $parameters['lat'] = $bias->lat;
            $parameters['lon'] = $bias->lon;
        }

        /** @var array{features?: list<PhotonFeature>} $data */
        $data = $this->photonClient
            ->request('GET', '/api', ['query' => $parameters])
            ->toArray();

        return array_map(
            static fn (array $place): Place => Place::fromArray($place),
            $data['features'] ?? [],
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function reverse(float $lat, float $lon, string $lang): ?Place
    {
        /** @var array{features?: list<PhotonFeature>} $data */
        $data = $this->photonClient
            ->request('GET', '/reverse', ['query' => ['lat' => $lat, 'lon' => $lon, 'lang' => $lang]])
            ->toArray();

        $features = $data['features'] ?? [];
        if ([] === $features) {
            return null;
        }

        return Place::fromArray($features[0]);
    }
}
