<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\User;
use App\Repository\CommandeRepository;
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
        ]);
    }
}
