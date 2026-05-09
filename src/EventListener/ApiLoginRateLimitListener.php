<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate-limits POST /api/login by client IP.
 *
 * Runs at priority 10 to fire BEFORE the firewall (~8) so a flood of bad
 * credentials never reaches the JSON authenticator.
 */
#[AsEventListener(event: RequestEvent::class, priority: 10)]
final class ApiLoginRateLimitListener
{
    public function __construct(
        #[Autowire(service: 'limiter.api_login')]
        private readonly RateLimiterFactory $factory,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getPathInfo() !== '/api/login' || $request->getMethod() !== 'POST') {
            return;
        }

        $limiter = $this->factory->create($request->getClientIp() ?? 'anonymous');
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = (int) max(1, $limit->getRetryAfter()->getTimestamp() - time());

            throw new TooManyRequestsHttpException(
                $retryAfter,
                'Too many login attempts. Try again later.',
            );
        }
    }
}
