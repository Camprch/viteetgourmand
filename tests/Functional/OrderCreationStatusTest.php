<?php

namespace App\Tests\Functional;

use App\Entity\Commande;
use App\Entity\Menu;

final class OrderCreationStatusTest extends FunctionalWebTestCase
{
    public function testOrderCreationCreatesInitialStatusAndDecrementsStock(): void
    {
        $user = $this->createUser('order-user@test.local', ['ROLE_USER']);
        $menu = $this->createMenu(stock: 3);
        $commune = $this->createCommune('0.00');

        $client = $this->createClientAs($user);
        $crawler = $client->request('GET', '/orders/new/' . $menu->getId());
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Valider la commande')->form([
            'order_form[adressePrestation]' => '10 rue Sainte-Catherine, Bordeaux',
            'order_form[datePrestation]' => (new \DateTimeImmutable('+5 days'))->format('Y-m-d'),
            'order_form[heurePrestation]' => '12:30',
            'order_form[communeLivraison]' => (string) $commune->getId(),
            'order_form[nbPersonnes]' => '4',
            'order_form[pretMateriel]' => '1',
        ]);

        $client->submit($form);
        self::assertResponseRedirects();

        $this->entityManager->clear();
        $commandes = $this->entityManager->getRepository(Commande::class)->findAll();
        self::assertCount(1, $commandes);

        $commande = $commandes[0];
        self::assertSame($user->getId(), $commande->getUser()?->getId());
        self::assertSame(4, $commande->getNbPersonnes());

        $statuts = $commande->getCommandeStatuts()->toArray();
        self::assertCount(1, $statuts);
        self::assertSame('en_attente', $statuts[0]->getStatut());

        $menuRefreshed = $this->entityManager->getRepository(Menu::class)->find($menu->getId());
        self::assertInstanceOf(Menu::class, $menuRefreshed);
        self::assertSame(2, $menuRefreshed->getStock());
    }
}
