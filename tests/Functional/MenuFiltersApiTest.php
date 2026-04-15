<?php

namespace App\Tests\Functional;

final class MenuFiltersApiTest extends FunctionalWebTestCase
{
    public function testApiMenusAppliesFiltersOnActiveMenus(): void
    {
        $menuClassic = $this->createMenu();
        $menuClassic
            ->setTitre('Menu classique')
            ->setTheme('classique')
            ->setRegime('classique')
            ->setPrixMinCentimes(3000)
            ->setPersonnesMin(4)
            ->setActif(true);

        $menuVegan = $this->createMenu();
        $menuVegan
            ->setTitre('Menu vegan')
            ->setTheme('event')
            ->setRegime('vegan')
            ->setPrixMinCentimes(4500)
            ->setPersonnesMin(8)
            ->setActif(true);

        $menuInactive = $this->createMenu();
        $menuInactive
            ->setTitre('Menu inactif')
            ->setTheme('classique')
            ->setRegime('classique')
            ->setPrixMinCentimes(2000)
            ->setPersonnesMin(2)
            ->setActif(false);

        $this->entityManager->flush();

        $client = static::createClient();

        $client->request('GET', '/api/menus');
        self::assertResponseIsSuccessful();
        $allPayload = json_decode($client->getResponse()->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $allPayload['count']);

        $client->request('GET', '/api/menus?theme=classique');
        self::assertResponseIsSuccessful();
        $themePayload = json_decode($client->getResponse()->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $themePayload['count']);
        self::assertSame('Menu classique', $themePayload['menus'][0]['titre']);

        $client->request('GET', '/api/menus?regime=vegan&personnes_min=6&prix_min=40&prix_max=50');
        self::assertResponseIsSuccessful();
        $combinedPayload = json_decode($client->getResponse()->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $combinedPayload['count']);
        self::assertSame('Menu vegan', $combinedPayload['menus'][0]['titre']);
    }
}
