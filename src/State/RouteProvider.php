<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Route;
use App\Client\OsrmClientInterface;
use App\Client\PhotonClientInterface;
use App\Dto\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * @implements ProviderInterface<Route>
 */
final readonly class RouteProvider implements ProviderInterface
{
    private const string COORDINATE_PATTERN = '/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/';

    public function __construct(
        private PhotonClientInterface $photon,
        private OsrmClientInterface $osrm,
        private RequestStack $requestStack,
        #[Autowire(env: 'DEFAULT_LANG')]
        private string $defaultLanguage,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Route
    {
        $request = $this->requestStack->getCurrentRequest();

        $from = trim((string) $request?->query->get('from', ''));
        $to = trim((string) $request?->query->get('to', ''));
        if ('' === $from || '' === $to) {
            throw new BadRequestHttpException('Query parameters "from" and "to" are required.');
        }

        $lang = trim((string) $request?->query->get('lang', '')) ?: $this->defaultLanguage;

        $origin = $this->resolve($from, $lang);
        $destination = $this->resolve($to, $lang);

        try {
            return $this->osrm->route($origin, $destination);
        } catch (ExceptionInterface|\RuntimeException $exception) {
            throw new HttpException(502, 'Routing backend unavailable.', $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    private function resolve(string $value, string $lang): Coordinate
    {
        if (1 === preg_match(self::COORDINATE_PATTERN, $value, $matches)) {
            return new Coordinate((float) $matches[1], (float) $matches[2]);
        }

        $places = $this->photon->geocode($value, null, 1, $lang);
        if ([] === $places) {
            throw new UnprocessableEntityHttpException(\sprintf('Address "%s" could not be geocoded.', $value));
        }

        return new Coordinate($places[0]->lat, $places[0]->lon);
    }
}
