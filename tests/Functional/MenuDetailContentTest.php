<?php

namespace App\Tests\Functional;

use App\Entity\Allergene;
use App\Entity\MenuImage;
use App\Entity\Plat;

final class MenuDetailContentTest extends FunctionalWebTestCase
{
    public function testMenuDetailShowsPlatsAllergenesAndGallerySection(): void
    {
        $menu = $this->createMenu();
        $menu->setTitre('Menu detail test');

        $allergene = (new Allergene())->setNom('Lactose');
        $plat = (new Plat())
            ->setNom('Tarte au citron')
            ->setType('dessert')
            ->setDescription('Dessert test')
            ->addAllergene($allergene);

        $image = (new MenuImage())
            ->setMenu($menu)
            ->setUrl('https://example.com/menu-detail.jpg')
            ->setAltText('Photo du menu detail test')
            ->setIsPrincipale(true)
            ->setOrdreAffichage(1);

        $menu->addPlat($plat);

        $this->entityManager->persist($allergene);
        $this->entityManager->persist($plat);
        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->request('GET', '/menus/' . $menu->getId());

        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('Galerie', $html);
        self::assertStringContainsString('Composition du menu', $html);
        self::assertStringContainsString('Tarte au citron', $html);
        self::assertStringContainsString('Lactose', $html);
        self::assertStringContainsString('menu-detail.jpg', $html);
    }
}
