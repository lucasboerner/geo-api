<?php

declare(strict_types=1);

namespace App\Cache;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class CacheFactory
{
    public function __construct(
        #[Autowire(env: 'bool:ENABLE_REDIS')]
        private bool $enableRedis,
        #[Autowire(env: 'REDIS_URL')]
        private string $redisUrl,
        #[Autowire('%kernel.cache_dir%')]
        private string $cacheDirectory,
    ) {
    }

    public function create(): CacheInterface
    {
        if ($this->enableRedis) {
            return new RedisAdapter(RedisAdapter::createConnection($this->redisUrl, ['lazy' => true]), 'geo');
        }

        return new FilesystemAdapter('geo', 0, $this->cacheDirectory.'/geo');
    }
}
