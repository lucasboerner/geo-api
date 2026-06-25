<?php

declare(strict_types=1);

namespace App\Client;

use App\ApiResource\Place;
use App\Dto\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsDecorator(PhotonClient::class)]
final readonly class CachedPhotonClient implements PhotonClientInterface
{
    public function __construct(
        #[AutowireDecorated]
        private PhotonClientInterface $inner,
        #[Autowire(service: 'geo.cache')]
        private CacheInterface $cache,
        #[Autowire(env: 'int:CACHE_TTL')]
        private int $ttl,
    ) {
    }

    /**
     * @return array<Place>
     */
    public function geocode(string $query, ?Coordinate $bias, int $limit, string $lang): array
    {
        /** @var array<Place> $places */
        $places = $this->cache->get(
            key: $this->geocodeKey($query, $bias, $limit, $lang),
            callback: function (ItemInterface $item) use ($query, $bias, $limit, $lang): array {
                $item->expiresAfter($this->ttl);

                return $this->inner->geocode($query, $bias, $limit, $lang);
            },
        );

        return $places;
    }

    public function reverse(float $lat, float $lon, string $lang): ?Place
    {
        /** @var Place|null $place */
        $place = $this->cache->get(
            key: $this->reverseKey($lat, $lon, $lang),
            callback: function (ItemInterface $item) use ($lat, $lon, $lang): ?Place {
                $item->expiresAfter($this->ttl);

                return $this->inner->reverse($lat, $lon, $lang);
            },
        );

        return $place;
    }

    private function geocodeKey(string $query, ?Coordinate $bias, int $limit, string $lang): string
    {
        $biasKey = null === $bias ? 'none' : sprintf('%.5f,%.5f', $bias->lat, $bias->lon);
        $raw = implode('|', [$lang, $limit, $biasKey, $this->normalize($query)]);

        return 'geocode.'.hash('xxh128', $raw);
    }

    private function reverseKey(float $lat, float $lon, string $lang): string
    {
        return 'reverse.'.hash('xxh128', sprintf('%s|%.6f|%.6f', $lang, $lat, $lon));
    }

    private function normalize(string $query): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim($query));

        return mb_strtolower($collapsed ?? '');
    }
}
