<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Main\Establishment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Establishment>
 */
class EstablishmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Establishment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name'),
            IntegerField::new('tenantId'),
            TextField::new('address'),
            AssociationField::new('user'),
        ];
    }
}
