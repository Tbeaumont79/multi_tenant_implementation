<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Main\Establishment;
use App\Entity\Main\TenantDbConfig;
use App\Entity\Main\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function index(): Response
    {
        $userRepo = $this->em->getRepository(User::class);
        $estabRepo = $this->em->getRepository(Establishment::class);
        $configRepo = $this->em->getRepository(TenantDbConfig::class);

        $totalUsers = $userRepo->count([]);
        $totalCabinets = $estabRepo->count([]);
        $totalConfigs = $configRepo->count([]);

        // Admins : on filtre côté PHP car roles est un JSON array dans Postgres
        $adminCount = 0;
        foreach ($userRepo->findAll() as $u) {
            if (\in_array('ROLE_ADMIN', $u->getRoles(), true)) {
                ++$adminCount;
            }
        }

        // Cabinets enrichis : nom + owner + status DB
        $cabinets = [];
        foreach ($estabRepo->findBy([], ['id' => 'ASC']) as $cabinet) {
            $config = $configRepo->findOneBy(['dbName' => 'cabinet'.$cabinet->getTenantId()]);
            $cabinets[] = [
                'name' => $cabinet->getName(),
                'tenantId' => $cabinet->getTenantId(),
                'address' => $cabinet->getAddress(),
                'owner' => $cabinet->getUser()?->getEmail() ?? '—',
                'dbStatus' => $config?->getDatabaseStatus() ?? DatabaseStatusEnum::DATABASE_NOT_CREATED,
                'dbName' => $config?->getDbName() ?? '—',
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalCabinets' => $totalCabinets,
            'totalConfigs' => $totalConfigs,
            'adminCount' => $adminCount,
            'cabinets' => $cabinets,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Multi Tenant Implementation');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(EstablishmentCrudController::class, 'Cabinets', 'fa fa-building');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
    }
}
