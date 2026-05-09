<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Main\Establishment;
use App\Entity\Main\TenantDbConfig;
use App\Service\Tenant\TenantProvisioner;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AfterEntityPersistedEvent::class)]
final class EstablishmentProvisioningListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TenantProvisioner $provisioner,
    ) {}
    
    /**
     * @param AfterEntityPersistedEvent<object> $event
     */
    public function __invoke(AfterEntityPersistedEvent $event): void
    {
        $establishment = $event->getEntityInstance();
        if (!$establishment instanceof Establishment) {
            return;
        }

        $dbName = 'cabinet' . $establishment->getTenantId();

        // Idempotent : si TenantDbConfig existe déjà pour ce dbName, on skip
        $existing = $this->em->getRepository(TenantDbConfig::class)
            ->findOneBy(['dbName' => $dbName]);
        if ($existing !== null) {
            return;
        }

        $config = (new TenantDbConfig())
            ->setDbName($dbName)
            ->setDbUserName('app')
            ->setDbPassword('app')
            ->setDbHost('127.0.0.1')
            ->setDbPort(5432);

        $this->em->persist($config);
        $this->em->flush();

        $this->provisioner->provision($config);
        $this->provisioner->migrate($config);
    }
}
