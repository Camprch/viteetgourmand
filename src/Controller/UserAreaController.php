<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\User;
use App\Form\ReviewFormType;
use App\Repository\CommandeRepository;
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
        ]);
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
}
