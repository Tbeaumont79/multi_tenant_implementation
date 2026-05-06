<?php

declare(strict_types=1);

namespace App\DataFixtures\Main;

use App\Entity\Main\Establishment;
use App\Entity\Main\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class EstablishmentFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const ESTABLISHMENT_REFERENCE = 'establishment_';
    private $establishments = [
        [
            'name' => 'Cabinet Médical Pasteur',
            'tenantId' => 1,
            'address' => '123 Rue Pasteur, 75015 Paris, France',
            'userIds' => [4],
        ],
        [
            'name' => 'Clinique Saint-Antoine',
            'tenantId' => 2,
            'address' => '456 Avenue Saint-Antoine, 75004 Paris, France',
            'userIds' => [5],
        ],
        [
            'name' => 'Centre Médical République',
            'tenantId' => 3,
            'address' => '789 Place de la République, 75003 Paris, France',
            'userIds' => [6],
        ],
        [
            'name' => 'Polyclinique Montparnasse',
            'tenantId' => 4,
            'address' => '101 Boulevard Montparnasse, 75014 Paris, France',
            'userIds' => [8],
        ],
    ];
    public function load(ObjectManager $manager): void
    {
        foreach ($this->establishments as $index => $establishment) {
            $newEstablishment = new Establishment();
            $newEstablishment->setName($establishment['name']);
            $newEstablishment->setTenantId($establishment['tenantId']);
            $newEstablishment->setAddress($establishment['address']);

            foreach ($establishment['userIds'] as $userId) {
                $user = $this->getReference(UserFixtures::USER_REFERENCE . $userId, User::class);
                $user->addEstablishment($newEstablishment);
            }

            $this->addReference(self::ESTABLISHMENT_REFERENCE . ($index + 1), $newEstablishment);
            $manager->persist($newEstablishment);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['main'];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
