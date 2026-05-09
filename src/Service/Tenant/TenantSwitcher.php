<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Entity\Main\Establishment;
use App\Entity\Main\TenantDbConfig;
use App\Entity\Main\User;
use Doctrine\ORM\EntityManagerInterface;
use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TenantSwitcher
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $events,
    ) {}

    /**
     * Loads the Establishment for the given tenantId, asserts the user owns it,
     * and dispatches a SwitchDbEvent so subsequent Doctrine queries hit the
     * correct tenant DB. Returns the resolved Establishment.
     */
    public function switchTo(User $user, int $tenantId): Establishment
    {
        $establishment = $this->em->getRepository(Establishment::class)
            ->findOneBy(['tenantId' => $tenantId]);

        if ($establishment === null || !$user->getEstablishments()->contains($establishment)) {
            throw new NotFoundHttpException('Cabinet not found or not accessible.');
        }

        $config = $this->em->getRepository(TenantDbConfig::class)
            ->findOneBy(['dbName' => 'cabinet' . $tenantId]);

        if ($config === null) {
            throw new \RuntimeException(sprintf('No TenantDbConfig for tenantId %d', $tenantId));
        }

        $this->events->dispatch(new SwitchDbEvent((string) $config->getId()));
        return $establishment;
    }
}
