<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\CommandeStatut;
use App\Entity\User;
use App\Form\ReviewFormType;
use App\Form\UserOrderEditType;
use App\Repository\CommandeRepository;
use App\Service\PricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
final class UserAreaController extends AbstractController
{
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/orders', name: 'app_profile_orders', methods: ['GET'])]
    public function orders(CommandeRepository $commandeRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $commandes = $commandeRepository->findBy(
            ['user' => $user],
            ['dateCommande' => 'DESC']
        );

        return $this->render('profile/orders.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/orders/{id}', name: 'app_profile_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showOrder(Commande $commande): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $statuts = $commande->getCommandeStatuts()->toArray();
        usort(
            $statuts,
            static fn ($a, $b): int => ($a->getDateHeure() <=> $b->getDateHeure())
        );

        return $this->render('profile/order_show.html.twig', [
            'commande' => $commande,
            'statuts' => $statuts,
            'canReview' => $this->canLeaveReview($commande),
            'canManage' => $this->canManageOrder($commande),
        ]);
    }

    #[Route('/orders/{id}/edit', name: 'app_profile_order_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editOrder(
        Commande $commande,
        Request $request,
        EntityManagerInterface $entityManager,
        PricingService $pricingService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->canManageOrder($commande)) {
            $this->addFlash('error', 'Modification impossible: cette commande a deja ete acceptee ou annulee.');

            return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
        }

        $form = $this->createForm(UserOrderEditType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commune = $commande->getCommuneLivraison();
            if ($commune === null) {
                $this->addFlash('error', 'Commune de livraison invalide.');

                return $this->redirectToRoute('app_profile_order_edit', ['id' => $commande->getId()]);
            }

            try {
                $pricing = $pricingService->calculate(
                    $commande->getMenu()?->getPrixMinCentimes() ?? 0,
                    (int) $commande->getNbPersonnes(),
                    $commande->getMenu()?->getPersonnesMin() ?? 1,
                    (float) $commune->getDistanceKm()
                );
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('profile/order_edit.html.twig', [
                    'commande' => $commande,
                    'orderForm' => $form,
                ]);
            }

            $commande
                ->setPrixMenuTotalCentimes($pricing['prix_menu_total_centimes'])
                ->setFraisLivraisonCentimes($pricing['frais_livraison_centimes'])
                ->setReductionAppliqueeCentimes($pricing['reduction_appliquee_centimes'])
                ->setPrixTotalCentimes($pricing['prix_total_centimes']);

            $entityManager->flush();

            $this->addFlash('success', 'Commande mise a jour.');

            return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
        }

        return $this->render('profile/order_edit.html.twig', [
            'commande' => $commande,
            'orderForm' => $form,
        ]);
    }

    #[Route('/orders/{id}/cancel', name: 'app_profile_order_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancelOrder(
        Commande $commande,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cancel_order_' . $commande->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
        }

        if (!$this->canManageOrder($commande)) {
            $this->addFlash('error', 'Annulation impossible: cette commande a deja ete acceptee ou annulee.');

            return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
        }

        $entityManager->persist(
            (new CommandeStatut())
                ->setCommande($commande)
                ->setUser($user)
                ->setStatut('annulee')
                ->setDateHeure(new \DateTimeImmutable())
                ->setCommentaire('Annulation demandee par le client depuis son espace')
        );
        $entityManager->flush();

        $this->addFlash('success', 'Commande annulee.');

        return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
    }

    #[Route('/orders/{id}/review', name: 'app_profile_order_review', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function reviewOrder(
        Commande $commande,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->canLeaveReview($commande)) {
            $this->addFlash('error', 'Avis impossible: commande non terminee ou avis deja depose.');

            return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
        }

        $avis = (new Avis())
            ->setCommande($commande)
            ->setValide(false)
            ->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ReviewFormType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($avis);
            $entityManager->flush();

            $this->addFlash('success', 'Merci, ton avis a ete enregistre et sera modere.');

            return $this->redirectToRoute('app_profile_order_show', ['id' => $commande->getId()]);
        }

        return $this->render('profile/review_new.html.twig', [
            'commande' => $commande,
            'reviewForm' => $form,
        ]);
    }

    private function canLeaveReview(Commande $commande): bool
    {
        if ($commande->getAvis() !== null) {
            return false;
        }

        $latestStatus = null;
        foreach ($commande->getCommandeStatuts() as $status) {
            if ($latestStatus === null || $status->getDateHeure() > $latestStatus->getDateHeure()) {
                $latestStatus = $status;
            }
        }

        return $latestStatus?->getStatut() === 'terminee';
    }

    private function canManageOrder(Commande $commande): bool
    {
        $latestStatus = null;
        foreach ($commande->getCommandeStatuts() as $status) {
            if ($latestStatus === null || $status->getDateHeure() > $latestStatus->getDateHeure()) {
                $latestStatus = $status;
            }

            if ($status->getStatut() === 'accepte') {
                return false;
            }
        }

        return $latestStatus?->getStatut() !== 'annulee';
    }
}
