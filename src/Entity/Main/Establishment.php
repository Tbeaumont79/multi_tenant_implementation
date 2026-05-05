<?php

namespace App\Entity\Main;

use App\Repository\Main\EstablishmentRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EstablishmentRepository::class)]
#[ApiResource]
class Establishment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Establishment name is required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Establishment name must be at least {{ limit }} characters long',
        maxMessage: 'Establishment name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Tenant ID is required')]
    #[Assert\Positive(message: 'Tenant ID must be a positive integer')]
    private ?int $tenantId = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: 'Address is required')]
    #[Assert\Length(
        min: 5,
        max: 500,
        minMessage: 'Address must be at least {{ limit }} characters long',
        maxMessage: 'Address cannot be longer than {{ limit }} characters'
    )]
    private ?string $address = null;

    #[ORM\ManyToOne(inversedBy: 'establishments')]
    #[Assert\NotNull(message: 'User is required')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function setTenantId(int $tenantId): static
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
