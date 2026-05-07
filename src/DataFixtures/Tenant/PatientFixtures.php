<?php

declare(strict_types=1);

namespace App\DataFixtures\Tenant;

use App\Entity\Tenant\Patient;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Hakam\MultiTenancyBundle\Attribute\TenantFixture;

#[TenantFixture]
class PatientFixtures extends Fixture
{
    private const DATASETS = [
        'cabinet1' => [
            ['firstName' => 'John',    'lastName' => 'Pers',    'nir' => '110122323454252', 'birthDate' => '1997-10-15'],
            ['firstName' => 'Emma',    'lastName' => 'Durand',  'nir' => '290155789654321', 'birthDate' => '1991-05-03'],
            ['firstName' => 'Lucas',   'lastName' => 'Martin',  'nir' => '190178456321987', 'birthDate' => '1999-11-10'],
            ['firstName' => 'Sophie',  'lastName' => 'Bernard', 'nir' => '280145632198745', 'birthDate' => '1924-07-17'],
            ['firstName' => 'Daniel',  'lastName' => 'Petit',   'nir' => '180165987321654', 'birthDate' => '1985-01-04'],
            ['firstName' => 'Clara',   'lastName' => 'Roux',    'nir' => '290188654987321', 'birthDate' => '1994-04-04'],
            ['firstName' => 'Maxime',  'lastName' => 'Girard',  'nir' => '190165478963258', 'birthDate' => '1993-09-02'],
            ['firstName' => 'Laura',   'lastName' => 'Dupuis',  'nir' => '290175896321478', 'birthDate' => '1996-12-25'],
            ['firstName' => 'Antoine', 'lastName' => 'Morel',   'nir' => '180154789632145', 'birthDate' => '1991-02-01'],
            ['firstName' => 'Camille', 'lastName' => 'Faure',   'nir' => '290168745963214', 'birthDate' => '1994-08-05'],
            ['firstName' => 'Julien',  'lastName' => 'Mercier', 'nir' => '190198745632589', 'birthDate' => '1989-06-10'],
        ],
        'cabinet2' => [
            ['firstName' => 'Marie',   'lastName' => 'Leblanc',  'nir' => '270122754003012', 'birthDate' => '1987-03-22'],
            ['firstName' => 'Pierre',  'lastName' => 'Garnier',  'nir' => '174087504123078', 'birthDate' => '1974-08-19'],
            ['firstName' => 'Léa',     'lastName' => 'Fontaine', 'nir' => '295067504220045', 'birthDate' => '1995-06-08'],
            ['firstName' => 'Théo',    'lastName' => 'Marchand', 'nir' => '101047504333089', 'birthDate' => '2001-04-27'],
            ['firstName' => 'Manon',   'lastName' => 'Aubry',    'nir' => '288127504112023', 'birthDate' => '1988-12-12'],
            ['firstName' => 'Hugo',    'lastName' => 'Vidal',    'nir' => '192057504244067', 'birthDate' => '1992-05-14'],
            ['firstName' => 'Inès',    'lastName' => 'Sanchez',  'nir' => '299037504355098', 'birthDate' => '1999-03-30'],
            ['firstName' => 'Noah',    'lastName' => 'Lefèvre',  'nir' => '105097504466034', 'birthDate' => '2005-09-09'],
            ['firstName' => 'Léna',    'lastName' => 'Renaud',   'nir' => '296017504577056', 'birthDate' => '1996-01-21'],
            ['firstName' => 'Adam',    'lastName' => 'Bouvier',  'nir' => '189117504688078', 'birthDate' => '1989-11-05'],
        ],
        'cabinet3' => [
            ['firstName' => 'Romane',   'lastName' => 'Caron',       'nir' => '294027503001045', 'birthDate' => '1994-02-14'],
            ['firstName' => 'Élise',    'lastName' => 'Picard',      'nir' => '283087503112089', 'birthDate' => '1983-08-03'],
            ['firstName' => 'Gabriel',  'lastName' => 'Rey',         'nir' => '178037503223067', 'birthDate' => '1978-03-26'],
            ['firstName' => 'Apolline', 'lastName' => 'Noël',        'nir' => '297127503334023', 'birthDate' => '1997-12-18'],
            ['firstName' => 'Tom',      'lastName' => 'Brunet',      'nir' => '102067503445078', 'birthDate' => '2002-06-07'],
            ['firstName' => 'Jade',     'lastName' => 'Charpentier', 'nir' => '298047503556012', 'birthDate' => '1998-04-22'],
            ['firstName' => 'Liam',     'lastName' => 'Joly',        'nir' => '104107503667045', 'birthDate' => '2004-10-15'],
            ['firstName' => 'Maëlys',   'lastName' => 'Hubert',      'nir' => '293057503778089', 'birthDate' => '1993-05-02'],
            ['firstName' => 'Ethan',    'lastName' => 'Pasquier',    'nir' => '195017503889034', 'birthDate' => '1995-01-29'],
        ],
        'cabinet4' => [
            ['firstName' => 'Sacha',     'lastName' => 'Gauthier',   'nir' => '198117514001023', 'birthDate' => '1998-11-11'],
            ['firstName' => 'Iris',      'lastName' => 'Rivière',    'nir' => '291037514112067', 'birthDate' => '1991-03-08'],
            ['firstName' => 'Noé',       'lastName' => 'Lemoine',    'nir' => '186067514223045', 'birthDate' => '1986-06-19'],
            ['firstName' => 'Lina',      'lastName' => 'Carpentier', 'nir' => '203097514334078', 'birthDate' => '2003-09-04'],
            ['firstName' => 'Constance', 'lastName' => 'Hamon',      'nir' => '276087514445089', 'birthDate' => '1976-08-25'],
            ['firstName' => 'Olivia',    'lastName' => 'Royer',      'nir' => '290127514556034', 'birthDate' => '1990-12-30'],
            ['firstName' => 'Eden',      'lastName' => 'Allard',     'nir' => '107047514667056', 'birthDate' => '2007-04-13'],
            ['firstName' => 'Mila',      'lastName' => 'Vincent',    'nir' => '299057514778012', 'birthDate' => '1999-05-06'],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $dbName = $manager->getConnection()->getDatabase();
        $patients = self::DATASETS[$dbName] ?? self::DATASETS['cabinet1'];

        foreach ($patients as $data) {
            $patient = (new Patient())
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setNir($data['nir'])
                ->setBirthDate(new DateTime($data['birthDate']));
            $manager->persist($patient);
        }
        $manager->flush();
    }
}
