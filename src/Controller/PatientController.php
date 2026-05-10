<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Main\User;
use App\Entity\Tenant\Patient;
use App\Form\PatientType;
use App\Service\Tenant\TenantSwitcher;
use Hakam\MultiTenancyBundle\Doctrine\ORM\TenantEntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class PatientController extends AbstractController
{
    private const SESSION_CABINET_KEY = 'active_cabinet_tenant_id';

    public function __construct(
        private readonly TenantEntityManager $tenantEm,
        private readonly TenantSwitcher $switcher,
    ) {
    }

    #[Route('/app/patients/new', name: 'app_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $activeCabinet = $this->resolveActiveCabinet($request);

        $patient = new Patient();
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tenantEm->persist($patient);
            $this->tenantEm->flush();

            $this->addFlash('success', \sprintf('Patient %s ajouté.', $patient->getFullName()));

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('patient/new.html.twig', [
            'form' => $form,
            'activeCabinet' => $activeCabinet,
        ]);
    }

    #[Route('/app/patients/{id}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $activeCabinet = $this->resolveActiveCabinet($request);

        $patient = $this->tenantEm->find(Patient::class, $id);
        if (null === $patient) {
            throw $this->createNotFoundException('Patient not found.');
        }

        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tenantEm->flush();

            $this->addFlash('success', \sprintf('Patient %s mis à jour.', $patient->getFullName()));

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('patient/edit.html.twig', [
            'form' => $form,
            'patient' => $patient,
            'activeCabinet' => $activeCabinet,
        ]);
    }

    #[Route('/app/patients/{id}/delete', name: 'app_patient_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): RedirectResponse
    {
        $this->resolveActiveCabinet($request);

        if (!$this->isCsrfTokenValid('delete-patient-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $patient = $this->tenantEm->find(Patient::class, $id);
        if (null === $patient) {
            throw $this->createNotFoundException('Patient not found.');
        }

        $name = $patient->getFullName();
        $this->tenantEm->remove($patient);
        $this->tenantEm->flush();

        $this->addFlash('success', \sprintf('Patient %s supprimé.', $name));

        return $this->redirectToRoute('app_dashboard');
    }

    private function resolveActiveCabinet(Request $request): \App\Entity\Main\Establishment
    {
        /** @var User $user */
        $user = $this->getUser();

        $tenantId = $request->getSession()->get(self::SESSION_CABINET_KEY);
        if (!\is_int($tenantId)) {
            throw new BadRequestHttpException('No active cabinet selected.');
        }

        return $this->switcher->switchTo($user, $tenantId);
    }
}
