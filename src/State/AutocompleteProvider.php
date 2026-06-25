<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Place;
use App\Client\PhotonClientInterface;
use App\Dto\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * @implements ProviderInterface<Place>
 */
final readonly class AutocompleteProvider implements ProviderInterface
{
    private const int DEFAULT_LIMIT = 8;

    public function __construct(
        private PhotonClientInterface $photon,
        private RequestStack $requestStack,
        #[Autowire(env: 'DEFAULT_LANG')]
        private string $defaultLanguage,
    ) {
    }

    /**
     * @return Place[]
     *
     * @throws ExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        $query = trim((string) $request?->query->get('query', ''));
        if ('' === $query) {
            throw new BadRequestHttpException('Query parameter "query" is required.');
        }

        $limit = (int) $request?->query->get('limit', self::DEFAULT_LIMIT);
        if ($limit < 1) {
            $limit = self::DEFAULT_LIMIT;
        }

        $bias = null;
        $lat = $request?->query->get('lat');
        $lon = $request?->query->get('lon');
        if (null !== $lat && null !== $lon) {
            $bias = new Coordinate((float) $lat, (float) $lon);
        }

        $lang = trim((string) $request?->query->get('lang', '')) ?: $this->defaultLanguage;

        return $this->photon->geocode($query, $bias, $limit, $lang);
    }
}
