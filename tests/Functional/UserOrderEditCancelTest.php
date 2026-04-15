<?php

namespace App\Tests\Functional;

use App\Entity\Commande;
use App\Entity\CommandeStatut;

final class UserOrderEditCancelTest extends FunctionalWebTestCase
{
    public function testUserCanEditAndCancelOrderBeforeAccepted(): void
    {
        $user = $this->createUser('user-edit-cancel@test.local', ['ROLE_USER']);
        $menu = $this->createMenu(stock: 3);
        $commune = $this->createCommune('8.00');
        $commande = $this->createCommande($user, $menu, $commune);
        $this->addStatus($commande, 'en_attente');

        $client = $this->createClientAs($user);

        $crawler = $client->request('GET', '/profile/orders/' . $commande->getId() . '/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer les modifications')->form([
            'user_order_edit[nomPrenomClient]' => 'Client Modifie',
            'user_order_edit[gsmClient]' => '0699999999',
            'user_order_edit[adressePrestation]' => '22 rue des Tests, Bordeaux',
            'user_order_edit[datePrestation]' => (new \DateTimeImmutable('+6 days'))->format('Y-m-d'),
            'user_order_edit[heurePrestation]' => '13:15',
            'user_order_edit[communeLivraison]' => (string) $commune->getId(),
            'user_order_edit[nbPersonnes]' => '9',
            'user_order_edit[pretMateriel]' => '1',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/profile/orders/' . $commande->getId());

        $this->entityManager->clear();
        $updated = $this->entityManager->getRepository(Commande::class)->find($commande->getId());
        self::assertInstanceOf(Commande::class, $updated);
        self::assertSame('Client Modifie', $updated->getNomPrenomClient());
        self::assertSame('0699999999', $updated->getGsmClient());
        self::assertSame(9, $updated->getNbPersonnes());
        self::assertTrue((bool) $updated->isPretMateriel());
        self::assertGreaterThan(0, (int) $updated->getPrixTotalCentimes());

        $crawler = $client->request('GET', '/profile/orders/' . $updated->getId());
        $cancelForm = $crawler->selectButton('Annuler la commande')->form();
        $client->submit($cancelForm);
        self::assertResponseRedirects('/profile/orders/' . $updated->getId());

        $this->entityManager->clear();
        $afterCancel = $this->entityManager->getRepository(Commande::class)->find($commande->getId());
        self::assertInstanceOf(Commande::class, $afterCancel);
        $statuses = $afterCancel->getCommandeStatuts()->toArray();
        usort($statuses, static fn ($a, $b): int => $a->getDateHeure() <=> $b->getDateHeure());
        self::assertNotEmpty($statuses);
        self::assertSame('annulee', end($statuses)->getStatut());
    }

    public function testUserCannotEditOrCancelAfterAccepted(): void
    {
        $user = $this->createUser('user-locked@test.local', ['ROLE_USER']);
        $employee = $this->createUser('employee-locked@test.local', ['ROLE_EMPLOYEE']);
        $menu = $this->createMenu(stock: 3);
        $commune = $this->createCommune('0.00');
        $commande = $this->createCommande($user, $menu, $commune);
        $this->addStatus($commande, 'en_attente');
        $this->addStatus($commande, 'accepte', $employee);

        $client = $this->createClientAs($user);

        $client->request('GET', '/profile/orders/' . $commande->getId() . '/edit');
        self::assertResponseRedirects('/profile/orders/' . $commande->getId());

        $crawler = $client->request('GET', '/profile/orders/' . $commande->getId());
        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->filter('a[href$="/edit"]')->count());
        self::assertSame(0, $crawler->filter('form[action$="/cancel"]')->count());
    }

    private function addStatus(Commande $commande, string $status, ?\App\Entity\User $actor = null): void
    {
        $statut = (new CommandeStatut())
            ->setCommande($commande)
            ->setUser($actor)
            ->setStatut($status)
            ->setDateHeure(new \DateTimeImmutable())
            ->setCommentaire(null);

        $this->entityManager->persist($statut);
        $this->entityManager->flush();
    }
}
