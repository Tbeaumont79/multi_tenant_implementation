<?php

declare(strict_types=1);

namespace App\DataFixtures\Main;

use App\Entity\Main\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $passwordHasher;
    public const USER_REFERENCE = 'user_';

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    private $users = [
        [
            'password' => 'Password123!',
            'email' => 'john@icloud.com',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'Test123!',
            'email' => 'sarah@gmail.com',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'Toto123!',
            'email' => 'mike@yahoo.com',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'Titi123!',
            'email' => 'emma@hotmail.com',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'Tata123!',
            'email' => 'alex@outlook.com',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'Tutu123!',
            'email' => 'lucas@protonmail.com',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'Power123!',
            'email' => 'sophie@wanadoo.fr',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'PowerTest123!',
            'email' => 'daniel@free.fr',
            'roles' => ['ROLE_USER'],
        ],
        [
            'password' => 'SuperAdmin123!',
            'email' => 'admin@myapp.com',
            'roles' => ['ROLE_ADMIN'],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->users as $index => $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->addReference(self::USER_REFERENCE.($index + 1), $user);

            $manager->persist($user);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'main',
        ];
    }
}
