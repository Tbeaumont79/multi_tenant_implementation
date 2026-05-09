<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Entity\Main\TenantDbConfig;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Event\TenantCreatedEvent;
use Hakam\MultiTenancyBundle\Port\TenantDatabaseManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

final class TenantProvisioner
{

    public function __construct(
        private readonly TenantDatabaseManagerInterface $tenantDbManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly KernelInterface $kernel
    ) {}

    public function provision(TenantDbConfig $config): void
    {
        $id = $config->getId();
        $dto = $this->tenantDbManager->getTenantDatabaseById($id);

        if (in_array($dto->dbStatus, [DatabaseStatusEnum::DATABASE_CREATED, DatabaseStatusEnum::DATABASE_MIGRATED], true)) {
            return;
        }

        $created = $this->tenantDbManager->createTenantDatabase($dto);
        if (!$created) {
            throw new \RuntimeException(sprintf('Failed to create tenant database %s', $dto->dbname));
        }

        $this->tenantDbManager->updateTenantDatabaseStatus($id, DatabaseStatusEnum::DATABASE_CREATED);
        $this->eventDispatcher->dispatch(new TenantCreatedEvent($id, $dto, $dto->dbname));
    }

    public function migrate(TenantDbConfig $config): void
    {
        $process = new Process(
            ['php', 'bin/console', 'tenant:migrations:migrate', 'init', (string) $config->getId(), '--no-interaction'],
            $this->kernel->getProjectDir(),
            null,
            null,
            120,
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'Tenant migration failed for db %s: %s',
                $config->getDbName(),
                $process->getErrorOutput() ?: $process->getOutput(),
            ));
        }
    }
}
