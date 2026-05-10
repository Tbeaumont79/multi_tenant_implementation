<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Main\Establishment;
use App\Entity\Main\User;
use App\Entity\Tenant\Patient;
use App\Security\Voter\EstablishmentVoter;
use App\Service\Tenant\TenantSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Hakam\MultiTenancyBundle\Doctrine\ORM\TenantEntityManager;
use LogicException;
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
        private readonly TenantSwitcher $switcher,
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

        $this->switcher->switchTo(
            $user,
            $activeCabinet->getTenantId() ?? throw new LogicException('Active cabinet has no tenantId.'),
        );

        $patients = $this->tenantEm
            ->getRepository(Patient::class)
            ->findBy([], ['lastName' => 'ASC']);

        // Bascule rapide : pointe vers le cabinet suivant dans la rotation.
        $nextCabinet = null;
        if (\count($cabinets) > 1) {
            $activeIndex = array_search($activeCabinet, $cabinets, true);
            $nextIndex = (false === $activeIndex ? 0 : $activeIndex + 1) % \count($cabinets);
            $nextCabinet = $cabinets[$nextIndex];
        }

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'cabinets' => $cabinets,
            'activeCabinet' => $activeCabinet,
            'nextCabinet' => $nextCabinet,
            'patients' => $patients,
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
