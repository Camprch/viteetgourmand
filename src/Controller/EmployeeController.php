<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\CommandeStatut;
use App\Entity\Horaire;
use App\Entity\Menu;
use App\Entity\User;
use App\Form\MenuType;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\HoraireRepository;
use App\Repository\MenuRepository;
use App\Service\OrderWorkflowService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employee')]
final class EmployeeController extends AbstractController
{
    private const DAY_LABELS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

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

    #[Route('/hours', name: 'app_employee_hours', methods: ['GET', 'POST'])]
    public function hours(
        Request $request,
        HoraireRepository $horaireRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->assertEmployeeAccess();
        $this->ensureWeeklyHours($horaireRepository, $entityManager);

        $horairesByDay = [];
        foreach ($horaireRepository->findOrderedByJour() as $horaire) {
            $horairesByDay[(int) $horaire->getJour()] = $horaire;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employee_hours_update', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('app_employee_hours');
            }

            for ($jour = 1; $jour <= 7; ++$jour) {
                $horaire = $horairesByDay[$jour] ?? null;
                if (!$horaire instanceof Horaire) {
                    continue;
                }

                $isClosed = $request->request->has('ferme_' . $jour);
                $opening = trim($request->request->getString('ouverture_' . $jour, ''));
                $closing = trim($request->request->getString('fermeture_' . $jour, ''));

                if ($isClosed) {
                    $horaire->setFerme(true);
                    $horaire->setHeureOuverture(null);
                    $horaire->setHeureFermeture(null);
                    continue;
                }

                if ($opening === '' || $closing === '') {
                    $this->addFlash('error', sprintf('Le jour %s doit avoir une ouverture et une fermeture.', self::DAY_LABELS[$jour]));

                    return $this->redirectToRoute('app_employee_hours');
                }

                $openingTime = \DateTimeImmutable::createFromFormat('!H:i', $opening);
                $closingTime = \DateTimeImmutable::createFromFormat('!H:i', $closing);
                if (!$openingTime instanceof \DateTimeImmutable || !$closingTime instanceof \DateTimeImmutable) {
                    $this->addFlash('error', sprintf('Format horaire invalide pour %s.', self::DAY_LABELS[$jour]));

                    return $this->redirectToRoute('app_employee_hours');
                }

                if ($openingTime >= $closingTime) {
                    $this->addFlash('error', sprintf('L\'heure de fermeture doit etre apres l\'ouverture (%s).', self::DAY_LABELS[$jour]));

                    return $this->redirectToRoute('app_employee_hours');
                }

                $horaire->setFerme(false);
                $horaire->setHeureOuverture($openingTime);
                $horaire->setHeureFermeture($closingTime);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Horaires mis a jour.');

            return $this->redirectToRoute('app_employee_hours');
        }

        return $this->render('employee/hours.html.twig', [
            'horaires' => $this->buildHoursViewModel($horairesByDay),
        ]);
    }

    #[Route('/menus', name: 'app_employee_menus', methods: ['GET'])]
    public function menus(MenuRepository $menuRepository): Response
    {
        $this->assertEmployeeAccess();

        return $this->render('employee/menus.html.twig', [
            'menus' => $menuRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/menus/new', name: 'app_employee_menu_new', methods: ['GET', 'POST'])]
    public function newMenu(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $menu = (new Menu())
            ->setActif(true)
            ->setStock(0)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setConditionsParticulieres(null);

        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($menu);
            $entityManager->flush();

            $this->addFlash('success', 'Menu cree.');

            return $this->redirectToRoute('app_employee_menus');
        }

        return $this->render('employee/menu_form.html.twig', [
            'menuForm' => $form,
            'isEdit' => false,
            'menu' => $menu,
        ]);
    }

    #[Route('/menus/{id}/edit', name: 'app_employee_menu_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editMenu(Menu $menu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Menu mis a jour.');

            return $this->redirectToRoute('app_employee_menus');
        }

        return $this->render('employee/menu_form.html.twig', [
            'menuForm' => $form,
            'isEdit' => true,
            'menu' => $menu,
        ]);
    }

    #[Route('/menus/{id}/delete', name: 'app_employee_menu_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteMenu(Menu $menu, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->assertEmployeeAccess();

        if (!$this->isCsrfTokenValid('delete_menu_' . $menu->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_employee_menus');
        }

        try {
            $entityManager->remove($menu);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Suppression impossible: ce menu est lie a des commandes.');

            return $this->redirectToRoute('app_employee_menus');
        }

        $this->addFlash('success', 'Menu supprime.');

        return $this->redirectToRoute('app_employee_menus');
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

    private function ensureWeeklyHours(HoraireRepository $horaireRepository, EntityManagerInterface $entityManager): void
    {
        $existingDays = [];
        foreach ($horaireRepository->findOrderedByJour() as $horaire) {
            $existingDays[(int) $horaire->getJour()] = true;
        }

        $hasChanges = false;
        for ($jour = 1; $jour <= 7; ++$jour) {
            if (isset($existingDays[$jour])) {
                continue;
            }

            $entityManager->persist(
                (new Horaire())
                    ->setJour($jour)
                    ->setFerme(true)
                    ->setHeureOuverture(null)
                    ->setHeureFermeture(null)
            );
            $hasChanges = true;
        }

        if ($hasChanges) {
            $entityManager->flush();
        }
    }

    /**
     * @param array<int, Horaire> $horairesByDay
     * @return list<array{jour: int, label: string, ferme: bool, ouverture: string, fermeture: string}>
     */
    private function buildHoursViewModel(array $horairesByDay): array
    {
        $result = [];
        for ($jour = 1; $jour <= 7; ++$jour) {
            $horaire = $horairesByDay[$jour] ?? null;
            $result[] = [
                'jour' => $jour,
                'label' => self::DAY_LABELS[$jour],
                'ferme' => (bool) ($horaire?->isFerme() ?? true),
                'ouverture' => $horaire?->getHeureOuverture()?->format('H:i') ?? '',
                'fermeture' => $horaire?->getHeureFermeture()?->format('H:i') ?? '',
            ];
        }

        return $result;
    }
}
