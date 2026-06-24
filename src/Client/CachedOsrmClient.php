<?php

declare(strict_types=1);

namespace App\Client;

use App\ApiResource\Route;
use App\Dto\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsDecorator(OsrmClient::class)]
final readonly class CachedOsrmClient implements OsrmClientInterface
{
    public function __construct(
        #[AutowireDecorated]
        private OsrmClientInterface $inner,
        #[Autowire(service: 'geo.cache')]
        private CacheInterface $cache,
        #[Autowire(env: 'int:CACHE_TTL')]
        private int $ttl,
    ) {
    }

    public function route(Coordinate $from, Coordinate $to): Route
    {
        /** @var Route $route */
        $route = $this->cache->get(
            key: $this->routeKey($from, $to),
            callback: function (ItemInterface $item) use ($from, $to): Route {
                $item->expiresAfter($this->ttl);

                return $this->inner->route($from, $to);
            },
        );

        return $route;
    }

    private function routeKey(Coordinate $from, Coordinate $to): string
    {
        $raw = sprintf('%.5f,%.5f;%.5f,%.5f', $from->lat, $from->lon, $to->lat, $to->lon);

        return 'route.'.hash('xxh128', $raw);
    }
}
