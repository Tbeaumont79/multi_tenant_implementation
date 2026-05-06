<?php

namespace App\DataFixtures\Tenant;

use Hakam\MultiTenancyBundle\Attribute\TenantFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Tenant\Patient;

#[TenantFixture]
class PatientFixtures extends Fixture
{

    private $patients = [
        [
            'firstName' => 'John',
            'lastName' => 'Pers',
            'nir' => '110122323454252',
            'birthDate' => '1997-10-15',
        ],
        [
            'firstName' => 'Emma',
            'lastName' => 'Durand',
            'nir' => '290155789654321',
            'birthDate' => '1991-05-03',
        ],
        [
            'firstName' => 'Lucas',
            'lastName' => 'Martin',
            'nir' => '190178456321987',
            'birthDate' => '1999-11-10',
        ],
        [
            'firstName' => 'Sophie',
            'lastName' => 'Bernard',
            'nir' => '280145632198745',
            'birthDate' => '1924-07-17',
        ],
        [
            'firstName' => 'Daniel',
            'lastName' => 'Petit',
            'nir' => '180165987321654',
            'birthDate' => '1985-01-04',
        ],
        [
            'firstName' => 'Clara',
            'lastName' => 'Roux',
            'nir' => '290188654987321',
            'birthDate' => '1994-04-04',
        ],
        [
            'firstName' => 'Maxime',
            'lastName' => 'Girard',
            'nir' => '190165478963258',
            'birthDate' => '1993-09-02',
        ],
        [
            'firstName' => 'Laura',
            'lastName' => 'Dupuis',
            'nir' => '290175896321478',
            'birthDate' => '1996-12-25',
        ],
        [
            'firstName' => 'Antoine',
            'lastName' => 'Morel',
            'nir' => '180154789632145',
            'birthDate' => '1991-02-01',
        ],
        [
            'firstName' => 'Camille',
            'lastName' => 'Faure',
            'nir' => '290168745963214',
            'birthDate' => '1994-08-05',
        ],
        [
            'firstName' => 'Julien',
            'lastName' => 'Mercier',
            'nir' => '190198745632589',
            'birthDate' => '1989-06-10',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < count($this->patients); $i++) {
            $newPatient = new Patient();
            $newPatient->setFirstName($this->patients[$i]['firstName']);
            $newPatient->setLastName($this->patients[$i]['lastName']);
            $newPatient->setNir($this->patients[$i]['nir']);
            $newPatient->setBirthDate(new \DateTime($this->patients[$i]['birthDate']));
            $manager->persist($newPatient);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['tenant'];
    }
}
