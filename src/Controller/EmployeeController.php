<?php

namespace App\Controller;

use App\Entity\Allergene;
use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\CommandeStatut;
use App\Entity\Horaire;
use App\Entity\Menu;
use App\Entity\MenuImage;
use App\Entity\Plat;
use App\Entity\User;
use App\Form\AllergeneType;
use App\Form\MenuType;
use App\Form\PlatType;
use App\Repository\AllergeneRepository;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\HoraireRepository;
use App\Repository\MenuRepository;
use App\Repository\PlatRepository;
use App\Service\MenuImageUploadService;
use App\Service\OrderWorkflowService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
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

    public function __construct(
        #[Autowire('%app.contact_sender%')]
        private readonly string $sender,
        private readonly MenuImageUploadService $menuImageUploadService,
    ) {
    }

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
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
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

        $this->sendOrderStatusNotification(
            commande: $commande,
            newStatus: $newStatus,
            modeContact: $modeContact,
            motif: $motif,
            mailer: $mailer,
        );

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
        $this->fillMenuImageFields($form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->syncMenuImagesFromForm($menu, $form, $request);
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->render('employee/menu_form.html.twig', [
                    'menuForm' => $form,
                    'isEdit' => false,
                    'menu' => $menu,
                    'menuImages' => $this->sortedMenuImages($menu),
                ]);
            }
            $entityManager->persist($menu);
            $entityManager->flush();

            $this->addFlash('success', 'Menu cree.');

            return $this->redirectToRoute('app_employee_menus');
        }

        return $this->render('employee/menu_form.html.twig', [
            'menuForm' => $form,
            'isEdit' => false,
            'menu' => $menu,
            'menuImages' => $this->sortedMenuImages($menu),
        ]);
    }

    #[Route('/menus/{id}/edit', name: 'app_employee_menu_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editMenu(Menu $menu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $form = $this->createForm(MenuType::class, $menu);
        $this->fillMenuImageFields($form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->syncMenuImagesFromForm($menu, $form, $request);
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->render('employee/menu_form.html.twig', [
                    'menuForm' => $form,
                    'isEdit' => true,
                    'menu' => $menu,
                    'menuImages' => $this->sortedMenuImages($menu),
                ]);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Menu mis a jour.');

            return $this->redirectToRoute('app_employee_menus');
        }

        return $this->render('employee/menu_form.html.twig', [
            'menuForm' => $form,
            'isEdit' => true,
            'menu' => $menu,
            'menuImages' => $this->sortedMenuImages($menu),
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

    #[Route('/allergenes', name: 'app_employee_allergenes', methods: ['GET'])]
    public function allergenes(AllergeneRepository $allergeneRepository): Response
    {
        $this->assertEmployeeAccess();

        return $this->render('employee/allergenes.html.twig', [
            'allergenes' => $allergeneRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/allergenes/new', name: 'app_employee_allergene_new', methods: ['GET', 'POST'])]
    public function newAllergene(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $allergene = new Allergene();
        $form = $this->createForm(AllergeneType::class, $allergene);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($allergene);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Cet allergene existe deja.');

                return $this->render('employee/allergene_form.html.twig', [
                    'allergeneForm' => $form,
                    'isEdit' => false,
                ]);
            }

            $this->addFlash('success', 'Allergene cree.');

            return $this->redirectToRoute('app_employee_allergenes');
        }

        return $this->render('employee/allergene_form.html.twig', [
            'allergeneForm' => $form,
            'isEdit' => false,
        ]);
    }

    #[Route('/allergenes/{id}/edit', name: 'app_employee_allergene_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editAllergene(Allergene $allergene, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $form = $this->createForm(AllergeneType::class, $allergene);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Cet allergene existe deja.');

                return $this->render('employee/allergene_form.html.twig', [
                    'allergeneForm' => $form,
                    'isEdit' => true,
                ]);
            }

            $this->addFlash('success', 'Allergene mis a jour.');

            return $this->redirectToRoute('app_employee_allergenes');
        }

        return $this->render('employee/allergene_form.html.twig', [
            'allergeneForm' => $form,
            'isEdit' => true,
        ]);
    }

    #[Route('/allergenes/{id}/delete', name: 'app_employee_allergene_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteAllergene(Allergene $allergene, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->assertEmployeeAccess();

        if (!$this->isCsrfTokenValid('delete_allergene_' . $allergene->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_employee_allergenes');
        }

        try {
            $entityManager->remove($allergene);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Suppression impossible: allergene encore lie a des plats.');

            return $this->redirectToRoute('app_employee_allergenes');
        }

        $this->addFlash('success', 'Allergene supprime.');

        return $this->redirectToRoute('app_employee_allergenes');
    }

    #[Route('/plats', name: 'app_employee_plats', methods: ['GET'])]
    public function plats(PlatRepository $platRepository): Response
    {
        $this->assertEmployeeAccess();

        return $this->render('employee/plats.html.twig', [
            'plats' => $platRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/plats/new', name: 'app_employee_plat_new', methods: ['GET', 'POST'])]
    public function newPlat(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $plat = new Plat();
        $form = $this->createForm(PlatType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($plat);
            $entityManager->flush();

            $this->addFlash('success', 'Plat cree.');

            return $this->redirectToRoute('app_employee_plats');
        }

        return $this->render('employee/plat_form.html.twig', [
            'platForm' => $form,
            'isEdit' => false,
        ]);
    }

    #[Route('/plats/{id}/edit', name: 'app_employee_plat_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editPlat(Plat $plat, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertEmployeeAccess();

        $form = $this->createForm(PlatType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Plat mis a jour.');

            return $this->redirectToRoute('app_employee_plats');
        }

        return $this->render('employee/plat_form.html.twig', [
            'platForm' => $form,
            'isEdit' => true,
        ]);
    }

    #[Route('/plats/{id}/delete', name: 'app_employee_plat_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePlat(Plat $plat, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->assertEmployeeAccess();

        if (!$this->isCsrfTokenValid('delete_plat_' . $plat->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_employee_plats');
        }

        try {
            $entityManager->remove($plat);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Suppression impossible: ce plat est lie a des menus.');

            return $this->redirectToRoute('app_employee_plats');
        }

        $this->addFlash('success', 'Plat supprime.');

        return $this->redirectToRoute('app_employee_plats');
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

    private function sendOrderStatusNotification(
        Commande $commande,
        string $newStatus,
        string $modeContact,
        string $motif,
        MailerInterface $mailer
    ): void {
        $user = $commande->getUser();
        if (!$user instanceof User || $user->getEmail() === null) {
            return;
        }

        $template = null;
        $subject = null;
        $context = [
            'commande' => $commande,
            'user' => $user,
            'modeContact' => $modeContact,
            'motif' => $motif,
        ];

        if ($newStatus === 'annulee') {
            $template = 'emails/order_cancelled.html.twig';
            $subject = 'Mise a jour de votre commande: annulation';
        } elseif ($newStatus === 'attente_retour_materiel') {
            $template = 'emails/order_material_return_notice.html.twig';
            $subject = 'Retour de materiel: information importante';
        } elseif ($newStatus === 'terminee') {
            $template = 'emails/order_review_request.html.twig';
            $subject = 'Votre commande est terminee - laissez votre avis';
        }

        if ($template === null || $subject === null) {
            return;
        }

        try {
            $mailer->send(
                (new TemplatedEmail())
                    ->from($this->sender)
                    ->to((string) $user->getEmail())
                    ->subject($subject)
                    ->htmlTemplate($template)
                    ->context($context)
            );
        } catch (TransportExceptionInterface) {
            // Keep status update successful even if mail transport is unavailable.
        }
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

    private function fillMenuImageFields(FormInterface $form): void
    {
        if ($form->has('imagePrincipaleAltText')) {
            $form->get('imagePrincipaleAltText')->setData('');
        }
        if ($form->has('imagesSupplementairesAltTexts')) {
            $form->get('imagesSupplementairesAltTexts')->setData('');
        }
    }

    private function syncMenuImagesFromForm(Menu $menu, FormInterface $form, Request $request): void
    {
        $existingImagesInput = $request->request->all('existing_images');
        $requestedMainIdRaw = trim($request->request->getString('main_image_id', ''));
        $requestedMainId = ctype_digit($requestedMainIdRaw) ? (int) $requestedMainIdRaw : null;

        $this->applyExistingImageChanges($menu, is_array($existingImagesInput) ? $existingImagesInput : []);

        $mainAlt = trim((string) $form->get('imagePrincipaleAltText')->getData());

        $forcedPrimary = null;
        $mainFile = $form->get('imagePrincipaleFile')->getData();
        if ($mainFile instanceof UploadedFile) {
            $forcedPrimary = $this->createImageFromUpload($menu, $mainFile, $mainAlt);
        }

        $extraFiles = $form->get('imagesSupplementairesFiles')->getData();
        $extraAltRaw = (string) $form->get('imagesSupplementairesAltTexts')->getData();
        $extraAlts = preg_split('/\R+/', trim($extraAltRaw)) ?: [];
        $extraIndex = 0;
        if (is_array($extraFiles)) {
            foreach ($extraFiles as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $alt = trim((string) ($extraAlts[$extraIndex] ?? ''));
                ++$extraIndex;
                $this->createImageFromUpload($menu, $file, $alt);
            }
        }

        $this->normalizeMenuImages($menu, $forcedPrimary, $requestedMainId);
    }

    /**
     * @param array<string, array{alt?: string, delete?: mixed}> $existingImagesInput
     */
    private function applyExistingImageChanges(Menu $menu, array $existingImagesInput): void
    {
        foreach ($menu->getMenuImages()->toArray() as $image) {
            $id = $image->getId();
            if ($id === null) {
                continue;
            }

            $row = $existingImagesInput[(string) $id] ?? $existingImagesInput[$id] ?? null;
            if (!is_array($row)) {
                continue;
            }

            if (!empty($row['delete'])) {
                $menu->removeMenuImage($image);
                continue;
            }

            $alt = trim((string) ($row['alt'] ?? ''));
            $image->setAltText($alt !== '' ? $alt : null);
        }
    }

    private function createImageFromUpload(Menu $menu, UploadedFile $file, string $altText): MenuImage
    {
        $storedPath = $this->menuImageUploadService->storeOptimized($file);
        $image = (new MenuImage())
            ->setUrl($storedPath)
            ->setAltText($altText !== '' ? $altText : null)
            ->setIsPrincipale(false)
            ->setOrdreAffichage(0);
        $menu->addMenuImage($image);

        return $image;
    }

    private function normalizeMenuImages(Menu $menu, ?MenuImage $forcedPrimary, ?int $requestedMainId): void
    {
        $images = $menu->getMenuImages()->toArray();
        if ($images === []) {
            return;
        }

        $primary = null;
        if ($forcedPrimary instanceof MenuImage) {
            $primary = $forcedPrimary;
        } elseif ($requestedMainId !== null) {
            foreach ($images as $image) {
                if ($image->getId() === $requestedMainId) {
                    $primary = $image;
                    break;
                }
            }
        }

        if (!$primary instanceof MenuImage) {
            foreach ($images as $image) {
                if ((bool) $image->isPrincipale()) {
                    $primary = $image;
                    break;
                }
            }
        }

        if (!$primary instanceof MenuImage) {
            $primary = $images[0];
        }

        foreach ($images as $image) {
            $image->setIsPrincipale(false);
        }
        $primary->setIsPrincipale(true);

        $others = array_values(array_filter($images, static fn (MenuImage $image): bool => $image !== $primary));
        usort($others, static function (MenuImage $a, MenuImage $b): int {
            $orderDiff = ($a->getOrdreAffichage() ?? 0) <=> ($b->getOrdreAffichage() ?? 0);
            if ($orderDiff !== 0) {
                return $orderDiff;
            }

            return ($a->getId() ?? 0) <=> ($b->getId() ?? 0);
        });

        $primary->setOrdreAffichage(1);
        $order = 2;
        foreach ($others as $image) {
            $image->setOrdreAffichage($order);
            ++$order;
        }
    }

    /**
     * @return list<MenuImage>
     */
    private function sortedMenuImages(Menu $menu): array
    {
        $images = $menu->getMenuImages()->toArray();
        usort($images, static function (MenuImage $a, MenuImage $b): int {
            if ((bool) $a->isPrincipale() !== (bool) $b->isPrincipale()) {
                return ((int) ($b->isPrincipale() ?? false)) <=> ((int) ($a->isPrincipale() ?? false));
            }

            $orderDiff = ($a->getOrdreAffichage() ?? 0) <=> ($b->getOrdreAffichage() ?? 0);
            if ($orderDiff !== 0) {
                return $orderDiff;
            }

            return ($a->getId() ?? 0) <=> ($b->getId() ?? 0);
        });

        return $images;
    }
}
