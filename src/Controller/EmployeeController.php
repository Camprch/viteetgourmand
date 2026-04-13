<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\CommandeStatut;
use App\Entity\User;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Service\OrderWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employee')]
final class EmployeeController extends AbstractController
{
    #[Route('', name: 'app_employee_dashboard', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        $this->assertEmployeeAccess();

        return $this->redirectToRoute('app_employee_orders');
    }

    #[Route('/orders', name: 'app_employee_orders', methods: ['GET'])]
    public function orders(
        Request $request,
        CommandeRepository $commandeRepository
    ): Response {
        $this->assertEmployeeAccess();

        $client = $request->query->getString('client', '');
        $status = $request->query->getString('status', '');

        $commandes = $commandeRepository->findForEmployeeList($client);
        $ordersData = [];
        foreach ($commandes as $commande) {
            $currentStatus = $this->getCurrentStatus($commande);
            if ($status !== '' && $currentStatus !== $status) {
                continue;
            }

            $ordersData[] = [
                'commande' => $commande,
                'current_status' => $currentStatus,
            ];
        }

        return $this->render('employee/orders.html.twig', [
            'ordersData' => $ordersData,
            'filters' => [
                'client' => $client,
                'status' => $status,
            ],
        ]);
    }

    #[Route('/orders/{id}', name: 'app_employee_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showOrder(
        Commande $commande,
        OrderWorkflowService $workflowService
    ): Response {
        $this->assertEmployeeAccess();

        $currentStatus = $this->getCurrentStatus($commande);
        $nextStatuses = $workflowService->nextStatuses($currentStatus ?? 'en_attente');

        $statuts = $commande->getCommandeStatuts()->toArray();
        usort(
            $statuts,
            static fn ($a, $b): int => ($a->getDateHeure() <=> $b->getDateHeure())
        );

        return $this->render('employee/order_show.html.twig', [
            'commande' => $commande,
            'statuts' => $statuts,
            'currentStatus' => $currentStatus,
            'nextStatuses' => $nextStatuses,
        ]);
    }

    #[Route('/orders/{id}/status', name: 'app_employee_order_update_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(
        Commande $commande,
        Request $request,
        OrderWorkflowService $workflowService,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $this->assertEmployeeAccess();

        $newStatus = trim($request->request->getString('new_status', ''));
        $modeContact = trim($request->request->getString('mode_contact', ''));
        $motif = trim($request->request->getString('motif', ''));

        $currentStatus = $this->getCurrentStatus($commande) ?? 'en_attente';

        if ($newStatus === '') {
            $this->addFlash('error', 'Choisis un nouveau statut.');

            return $this->redirectToRoute('app_employee_order_show', ['id' => $commande->getId()]);
        }

        try {
            $workflowService->assertTransition($currentStatus, $newStatus);
        } catch (\DomainException) {
            $this->addFlash('error', 'Transition de statut non autorisee.');

            return $this->redirectToRoute('app_employee_order_show', ['id' => $commande->getId()]);
        }

        if ($newStatus === 'annulee' && ($modeContact === '' || $motif === '')) {
            $this->addFlash('error', 'Pour annuler, precise le mode de contact et le motif.');

            return $this->redirectToRoute('app_employee_order_show', ['id' => $commande->getId()]);
        }

        $employee = $this->getUser();
        if (!$employee instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $commentaire = $motif;
        if ($modeContact !== '') {
            $commentaire = sprintf('[%s] %s', $modeContact, $motif);
        }

        $statut = (new CommandeStatut())
            ->setCommande($commande)
            ->setUser($employee)
            ->setStatut($newStatus)
            ->setDateHeure(new \DateTimeImmutable())
            ->setCommentaire($commentaire !== '' ? $commentaire : null);

        $entityManager->persist($statut);
        $entityManager->flush();

        $this->addFlash('success', 'Statut mis a jour.');

        return $this->redirectToRoute('app_employee_order_show', ['id' => $commande->getId()]);
    }

    #[Route('/reviews', name: 'app_employee_reviews', methods: ['GET'])]
    public function reviews(AvisRepository $avisRepository): Response
    {
        $this->assertEmployeeAccess();

        return $this->render('employee/reviews.html.twig', [
            'reviews' => $avisRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/reviews/{id}/moderate', name: 'app_employee_review_moderate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function moderateReview(
        Avis $avis,
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $this->assertEmployeeAccess();

        if (!$this->isCsrfTokenValid('moderate_review_' . $avis->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_employee_reviews');
        }

        $action = $request->request->getString('action', '');
        if (!in_array($action, ['validate', 'reject'], true)) {
            $this->addFlash('error', 'Action de moderation invalide.');

            return $this->redirectToRoute('app_employee_reviews');
        }

        $avis->setValide($action === 'validate');
        $entityManager->flush();

        $this->addFlash('success', $action === 'validate' ? 'Avis valide.' : 'Avis refuse.');

        return $this->redirectToRoute('app_employee_reviews');
    }

    private function getCurrentStatus(Commande $commande): ?string
    {
        $latest = null;
        foreach ($commande->getCommandeStatuts() as $statut) {
            if ($latest === null || $statut->getDateHeure() > $latest->getDateHeure()) {
                $latest = $statut;
            }
        }

        return $latest?->getStatut();
    }

    private function assertEmployeeAccess(): void
    {
        if (!$this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }
}
