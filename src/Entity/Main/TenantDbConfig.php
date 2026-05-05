<?php

namespace App\Entity\Main;

use Doctrine\ORM\Mapping as ORM;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Hakam\MultiTenancyBundle\Services\TenantDbConfigurationInterface;
use Hakam\MultiTenancyBundle\Traits\TenantDbConfigTrait;

#[ORM\Entity]
#[ORM\Table(name: 'tenant_db_config')]
class TenantDbConfig implements TenantDbConfigurationInterface
{
    use TenantDbConfigTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct()
    {
        $this->driverType = DriverTypeEnum::POSTGRES;
    }

    public function getId(): int
    {
        return $this->id;
    }

    // IMPORTANT: PHP method names are case-insensitive.
    // The trait defines getDbUserName() and the interface defines getDbUsername().
    // Access the property directly to avoid infinite recursion.
    public function getDbUsername(): ?string
    {
        return $this->dbUserName;
    }

    public function getIdentifierValue(): mixed
    {
        return $this->id;
    }
}
