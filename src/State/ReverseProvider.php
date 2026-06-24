<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Place;
use App\Client\PhotonClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * @implements ProviderInterface<Place>
 */
final readonly class ReverseProvider implements ProviderInterface
{
    public function __construct(
        private PhotonClientInterface $photon,
        private RequestStack $requestStack,
        #[Autowire(env: 'DEFAULT_LANG')]
        private string $defaultLanguage,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Place
    {
        $request = $this->requestStack->getCurrentRequest();

        $lat = $request?->query->get('lat');
        $lon = $request?->query->get('lon');
        if (null === $lat || null === $lon) {
            throw new BadRequestHttpException('Query parameters "lat" and "lon" are required.');
        }

        return $this->photon->reverse((float) $lat, (float) $lon, $this->defaultLanguage);
    }
}
