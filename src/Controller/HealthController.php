<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HealthController
{
    public function __construct(
        private HttpClientInterface $photonClient,
        private HttpClientInterface $osrmClient,
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $photonReachable = $this->isReachable($this->photonClient, '/api', ['q' => 'health', 'limit' => 1]);
        $osrmReachable = $this->isReachable($this->osrmClient, '/nearest/v1/driving/0,0', []);
        $healthy = $photonReachable && $osrmReachable;

        return new JsonResponse(
            [
                'status' => $healthy ? 'ok' : 'unavailable',
                'photon' => $photonReachable,
                'osrm' => $osrmReachable,
            ],
            $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    /**
     * @param array<string, scalar> $query
     */
    private function isReachable(HttpClientInterface $client, string $path, array $query): bool
    {
        try {
            $statusCode = $client
                ->request('GET', $path, ['query' => $query, 'timeout' => 2.0])
                ->getStatusCode();

            return $statusCode >= Response::HTTP_OK && $statusCode < Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch (\Throwable) {
            return false;
        }
    }
}
