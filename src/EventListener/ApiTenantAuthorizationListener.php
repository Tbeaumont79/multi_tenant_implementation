<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Main\Establishment;
use App\Entity\Main\TenantDbConfig;
use App\Security\Voter\EstablishmentVoter;
use Doctrine\ORM\EntityManagerInterface;
use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Hakam\MultiTenancyBundle\Model\TenantEntityInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsEventListener(event: RequestEvent::class, priority: 0)]
final class ApiTenantAuthorizationListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $events,
        private readonly Security $security,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $resourceClass = $event->getRequest()->attributes->get('_api_resource_class');
        if (
            !is_string($resourceClass)
            || !is_subclass_of($resourceClass, TenantEntityInterface::class)
        ) {
            return;
        }

        $tenantId = $event->getRequest()->headers->get('X-Tenant-Id');
        if (!$tenantId) {
            throw new AccessDeniedHttpException('You are not authorized to access this resource');
        }
        $establishment = $this->em->getRepository(Establishment::class)
            ->findOneBy(['tenantId' => $tenantId]);
        if (!$establishment) {
            throw new BadRequestHttpException('Establishment not found');
        }
        if (!$this->security->isGranted(EstablishmentVoter::SWITCH, $establishment)) {
            throw new AccessDeniedHttpException('You are not authorized to access this resource');
        }
        $tenantDbConfig = $this->em->getRepository(TenantDbConfig::class)
            ->findOneBy(['dbName' => 'cabinet' . $tenantId]);
        if (!$tenantDbConfig) {
            throw new BadRequestHttpException('Tenant DB config not found');
        }
        $this->events->dispatch(new SwitchDbEvent((string) $tenantDbConfig->getId()));
    }
}
