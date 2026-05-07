<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Main\Establishment;
use App\Entity\Main\TenantDbConfig;
use App\Entity\Main\User;
use App\Entity\Tenant\Patient;
use App\Security\Voter\EstablishmentVoter;
use Doctrine\ORM\EntityManagerInterface;
use Hakam\MultiTenancyBundle\Doctrine\ORM\TenantEntityManager;
use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    private const SESSION_CABINET_KEY = 'active_cabinet_tenant_id';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TenantEntityManager $tenantEm,
        private readonly EventDispatcherInterface $events,
    ) {
    }

    #[Route('/app', name: 'app_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $cabinets = $this->em->getRepository(Establishment::class)
            ->findBy(['user' => $user], ['id' => 'ASC']);

        if (empty($cabinets)) {
            return $this->render('dashboard/empty.html.twig', [
                'user' => $user,
            ]);
        }

        $session = $request->getSession();
        $sessionTenantId = $session->get(self::SESSION_CABINET_KEY);

        $activeCabinet = null;
        foreach ($cabinets as $cabinet) {
            if ($cabinet->getTenantId() === $sessionTenantId) {
                $activeCabinet = $cabinet;
                break;
            }
        }
        if (null === $activeCabinet) {
            $activeCabinet = $cabinets[0];
            $session->set(self::SESSION_CABINET_KEY, $activeCabinet->getTenantId());
        }

        $tenantConfig = $this->em->getRepository(TenantDbConfig::class)
            ->findOneBy(['dbName' => 'cabinet'.$activeCabinet->getTenantId()]);

        $patients = [];
        if (null !== $tenantConfig) {
            $this->events->dispatch(new SwitchDbEvent((string) $tenantConfig->getId()));
            $patients = $this->tenantEm
                ->getRepository(Patient::class)
                ->findBy([], ['lastName' => 'ASC']);
        }

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'cabinets' => $cabinets,
            'activeCabinet' => $activeCabinet,
            'patients' => $patients,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    #[Route('/app/cabinet/{tenantId}', name: 'app_cabinet_switch', methods: ['GET', 'POST'], requirements: ['tenantId' => '\d+'])]
    public function switch(int $tenantId, Request $request): RedirectResponse
    {
        $establishment = $this->em->getRepository(Establishment::class)
            ->findOneBy(['tenantId' => $tenantId]);
        if (null === $establishment) {
            throw $this->createNotFoundException('Establishment not found');
        }
        $this->denyAccessUnlessGranted(EstablishmentVoter::SWITCH, $establishment);
        $session = $request->getSession();
        $session->set(self::SESSION_CABINET_KEY, $tenantId);

        return $this->redirectToRoute('app_dashboard');
    }
}
