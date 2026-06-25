<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final readonly class ApiKeyListener
{
    private const string HEALTH_PATH = '/health';

    public function __construct(#[Autowire(env: 'API_KEY')] private string $apiKey)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || '' === $this->apiKey) {
            return;
        }

        $request = $event->getRequest();
        if ($request->isMethod('OPTIONS') || self::HEALTH_PATH === $request->getPathInfo()) {
            return;
        }

        $provided = $request->headers->get('X-API-Key');

        if (null === $provided || !hash_equals($this->apiKey, $provided)) {
            throw new UnauthorizedHttpException('X-API-Key', 'A valid API key is required.');
        }
    }
}
