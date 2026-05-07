<?php

declare(strict_types=1);

namespace App\DataFixtures\Main;

use App\Entity\Main\TenantDbConfig;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;

class TenantDbConfigFixtures extends Fixture implements FixtureGroupInterface
{
    private $tenantConfigs = [
        1 => [
            'dbName' => 'cabinet1',
            'dbUsername' => 'app',
            'dbHost' => '127.0.0.1',
            'dbPort' => 5432,
            'dbPassword' => 'app',
        ],
        2 => [
            'dbName' => 'cabinet2',
            'dbUsername' => 'app',
            'dbHost' => '127.0.0.1',
            'dbPassword' => 'app',
            'dbPort' => 5432,
        ],
        3 => [
            'dbName' => 'cabinet3',
            'dbUsername' => 'app',
            'dbHost' => '127.0.0.1',
            'dbPassword' => 'app',
            'dbPort' => 5432,
        ],
        4 => [
            'dbName' => 'cabinet4',
            'dbUsername' => 'app',
            'dbPassword' => 'app',
            'dbHost' => '127.0.0.1',
            'dbPort' => 5432,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->tenantConfigs as $key => $tenantConfig) {
            $newTenant = new TenantDbConfig();
            $newTenant->setDbName($tenantConfig['dbName']);
            $newTenant->setDbUserName($tenantConfig['dbUsername']);
            $newTenant->setDbHost($tenantConfig['dbHost']);
            $newTenant->setDbPort($tenantConfig['dbPort']);
            $newTenant->setDbPassword($tenantConfig['dbPassword']);
            $newTenant->setDriverType(DriverTypeEnum::POSTGRES);
            $newTenant->setDatabaseStatus(DatabaseStatusEnum::DATABASE_NOT_CREATED); // it will be switched to DATABASE_CREATED when we will execute the command
            $manager->persist($newTenant);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['main'];
    }
}
