<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Main\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            EmailField::new('email'),
            ChoiceField::new('roles')
                ->allowMultipleChoices()
                ->setChoices(['User' => 'ROLE_USER', 'Admin' => 'ROLE_ADMIN']),
            TextField::new('password')
                ->setFormType(PasswordType::class)
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->setHelp($pageName === Crud::PAGE_EDIT ? 'Laisser vide pour ne pas changer.' : '')
                ->onlyOnForms(),
        ];
    }

    public function persistEntity(EntityManagerInterface $em, $entity): void
    {
        $this->hashPasswordIfPresent($entity);
        parent::persistEntity($em, $entity);
    }

    public function updateEntity(EntityManagerInterface $em, $entity): void
    {
        $this->hashPasswordIfPresent($entity);
        parent::updateEntity($em, $entity);
    }

    private function hashPasswordIfPresent(User $user): void
    {
        $plain = $user->getPassword();
        if ($plain !== null && $plain !== '' && !str_starts_with($plain, '$2y$')) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
        }
    }
}
