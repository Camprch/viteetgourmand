<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeStatut;
use App\Entity\Menu;
use App\Entity\User;
use App\Form\OrderFormType;
use App\Service\PricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_order_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(
        Menu $menu,
        Request $request,
        EntityManagerInterface $entityManager,
        PricingService $pricingService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($menu->getStock() !== null && $menu->getStock() <= 0) {
            $this->addFlash('error', 'Ce menu est indisponible (stock epuise).');

            return $this->redirectToRoute('app_menu_show', ['id' => $menu->getId()]);
        }

        $nomPrenomClient = trim(sprintf('%s %s', $user->getPrenom() ?? '', $user->getNom() ?? ''));
        $gsmClient = (string) ($user->getTelephone() ?? '');

        if ($gsmClient === '') {
            $this->addFlash('error', 'Ajoute d abord un numero de telephone dans ton profil pour commander.');

            return $this->redirectToRoute('app_menu_show', ['id' => $menu->getId()]);
        }

        $form = $this->createForm(OrderFormType::class, null, [
            'nom_prenom_client' => $nomPrenomClient,
            'gsm_client' => $gsmClient,
        ]);
        $form->handleRequest($request);

        $pricing = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $commune = $form->get('communeLivraison')->getData();
            $nbPersonnes = (int) $form->get('nbPersonnes')->getData();
            $distanceKm = (float) ($commune?->getDistanceKm() ?? 0.0);

            try {
                $pricing = $pricingService->calculate(
                    $menu->getPrixMinCentimes() ?? 0,
                    $nbPersonnes,
                    $menu->getPersonnesMin() ?? 1,
                    $distanceKm
                );
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('order/new.html.twig', [
                    'menu' => $menu,
                    'orderForm' => $form,
                    'pricing' => null,
                ]);
            }

            $commande = (new Commande())
                ->setUser($user)
                ->setMenu($menu)
                ->setCommuneLivraison($commune)
                ->setDateCommande(new \DateTimeImmutable())
                ->setDatePrestation($form->get('datePrestation')->getData())
                ->setHeurePrestation($form->get('heurePrestation')->getData())
                ->setAdressePrestation((string) $form->get('adressePrestation')->getData())
                ->setNomPrenomClient($nomPrenomClient)
                ->setGsmClient($gsmClient)
                ->setPrixMenuTotalCentimes($pricing['prix_menu_total_centimes'])
                ->setFraisLivraisonCentimes($pricing['frais_livraison_centimes'])
                ->setReductionAppliqueeCentimes($pricing['reduction_appliquee_centimes'])
                ->setPrixTotalCentimes($pricing['prix_total_centimes'])
                ->setNbPersonnes($nbPersonnes)
                ->setPretMateriel((bool) $form->get('pretMateriel')->getData());

            $statutInitial = (new CommandeStatut())
                ->setCommande($commande)
                ->setUser(null)
                ->setStatut('en_attente')
                ->setDateHeure(new \DateTimeImmutable())
                ->setCommentaire('Commande en attente de validation equipe');

            $menu->setStock(max(0, ($menu->getStock() ?? 0) - 1));

            $entityManager->persist($commande);
            $entityManager->persist($statutInitial);
            $entityManager->flush();

            $this->addFlash('success', 'Commande enregistree avec succes.');

            return $this->redirectToRoute('app_order_show', ['id' => $commande->getId()]);
        }

        return $this->render('order/new.html.twig', [
            'menu' => $menu,
            'orderForm' => $form,
            'pricing' => $pricing,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Commande $commande): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getUser()?->getId() !== $user->getId() && !$this->isGranted('ROLE_EMPLOYEE')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/show.html.twig', [
            'commande' => $commande,
        ]);
    }
}
