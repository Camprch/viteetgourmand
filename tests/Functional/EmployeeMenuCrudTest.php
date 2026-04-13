<?php

namespace App\Tests\Functional;

use App\Entity\Menu;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class EmployeeMenuCrudTest extends FunctionalWebTestCase
{
    public function testMenusPageAccessByRole(): void
    {
        $user = $this->createUser('user-menus@test.local', ['ROLE_USER']);
        $employee = $this->createUser('employee-menus@test.local', ['ROLE_EMPLOYEE']);

        $guestClient = static::createClient();
        $guestClient->request('GET', '/employee/menus');
        self::assertResponseRedirects('/login');

        $userClient = $this->createClientAs($user);
        $userClient->request('GET', '/employee/menus');
        self::assertResponseStatusCodeSame(403);

        $employeeClient = $this->createClientAs($employee);
        $employeeClient->request('GET', '/employee/menus');
        self::assertResponseIsSuccessful();
    }

    public function testEmployeeCanCreateEditAndDeleteMenu(): void
    {
        $employee = $this->createUser('employee-menus-crud@test.local', ['ROLE_EMPLOYEE']);
        $client = $this->createClientAs($employee);

        $crawler = $client->request('GET', '/employee/menus/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Creer')->form([
            'menu[titre]' => 'Menu printemps',
            'menu[description]' => 'Menu de saison pour tests fonctionnels',
            'menu[theme]' => 'printemps',
            'menu[regime]' => 'classique',
            'menu[prixMinCentimes]' => '3500',
            'menu[personnesMin]' => '6',
            'menu[stock]' => '9',
            'menu[conditionsParticulieres]' => 'Commander 72h avant',
            'menu[actif]' => '1',
        ]);

        $client->submit($form);
        self::assertResponseRedirects('/employee/menus');

        $this->entityManager->clear();
        $menu = $this->entityManager->getRepository(Menu::class)->findOneBy(['titre' => 'Menu printemps']);
        self::assertInstanceOf(Menu::class, $menu);
        self::assertSame(3500, $menu->getPrixMinCentimes());
        self::assertSame(9, $menu->getStock());

        $crawler = $client->request('GET', '/employee/menus/' . $menu->getId() . '/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'menu[titre]' => 'Menu printemps maj',
            'menu[description]' => 'Menu de saison modifie',
            'menu[theme]' => 'printemps',
            'menu[regime]' => 'vegetarien',
            'menu[prixMinCentimes]' => '3900',
            'menu[personnesMin]' => '6',
            'menu[stock]' => '8',
            'menu[conditionsParticulieres]' => 'Commander 48h avant',
            'menu[actif]' => '1',
        ]);

        $client->submit($form);
        self::assertResponseRedirects('/employee/menus');

        $this->entityManager->clear();
        $updatedMenu = $this->entityManager->getRepository(Menu::class)->find($menu->getId());
        self::assertInstanceOf(Menu::class, $updatedMenu);
        self::assertSame('Menu printemps maj', $updatedMenu->getTitre());
        self::assertSame(3900, $updatedMenu->getPrixMinCentimes());

        $tokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
        $token = $tokenManager->getToken('delete_menu_' . $updatedMenu->getId())->getValue();

        $client->request('POST', '/employee/menus/' . $updatedMenu->getId() . '/delete', [
            '_token' => $token,
        ]);
        self::assertResponseRedirects('/employee/menus');

        $this->entityManager->clear();
        $deletedMenu = $this->entityManager->getRepository(Menu::class)->find($updatedMenu->getId());
        self::assertNull($deletedMenu);
    }
}
