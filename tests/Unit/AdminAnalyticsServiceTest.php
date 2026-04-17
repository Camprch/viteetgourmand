<?php

namespace App\Tests\Unit;

use App\Repository\CommandeRepository;
use App\Service\AdminAnalyticsService;
use MongoDB\Client;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;

final class AdminAnalyticsServiceTest extends TestCase
{
    public function testCountOrdersByMenuMapsAggregationRows(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection
            ->expects(self::once())
            ->method('aggregate')
            ->willReturn(new \ArrayIterator([
                ['_id' => ['menu_id' => 10, 'menu_titre' => 'Menu A'], 'commandes' => 3],
                ['_id' => ['menu_id' => 20, 'menu_titre' => 'Menu B'], 'commandes' => 1],
            ]));

        $client = $this->createMock(Client::class);
        $client
            ->expects(self::once())
            ->method('selectCollection')
            ->with('viteetgourmand_stats', 'order_stats')
            ->willReturn($collection);

        $service = new AdminAnalyticsService(
            $client,
            'viteetgourmand_stats',
            $this->createMock(CommandeRepository::class)
        );

        $rows = $service->countOrdersByMenu();

        self::assertSame([
            ['menu_id' => 10, 'menu_titre' => 'Menu A', 'commandes' => 3],
            ['menu_id' => 20, 'menu_titre' => 'Menu B', 'commandes' => 1],
        ], $rows);
    }

    public function testRevenueByMenuMapsAggregationRows(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection
            ->expects(self::once())
            ->method('aggregate')
            ->willReturn(new \ArrayIterator([
                [
                    '_id' => ['menu_id' => 3, 'menu_titre' => 'Menu Noel'],
                    'commandes' => 2,
                    'chiffre_affaires_centimes' => 71600,
                ],
            ]));

        $client = $this->createMock(Client::class);
        $client
            ->expects(self::once())
            ->method('selectCollection')
            ->with('viteetgourmand_stats', 'order_stats')
            ->willReturn($collection);

        $service = new AdminAnalyticsService(
            $client,
            'viteetgourmand_stats',
            $this->createMock(CommandeRepository::class)
        );

        $rows = $service->revenueByMenu(null, null, null);

        self::assertSame([
            [
                'menu_id' => 3,
                'menu_titre' => 'Menu Noel',
                'commandes' => 2,
                'chiffre_affaires_centimes' => 71600,
            ],
        ], $rows);
    }
}
