<?php

namespace App\Service;

use App\Repository\CommandeRepository;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

final class AdminAnalyticsService
{
    private const COLLECTION = 'order_stats';

    public function __construct(
        private readonly Client $mongoClient,
        private readonly string $mongoDbName,
        private readonly CommandeRepository $commandeRepository,
    ) {
    }

    /**
     * Build/refresh a Mongo projection of SQL orders.
     */
    public function refreshProjectionFromSql(): void
    {
        $collection = $this->collection();
        foreach ($this->commandeRepository->findAllForAnalyticsProjection() as $row) {
            $dateCommande = $row['date_commande'];
            if (!$dateCommande instanceof \DateTimeImmutable) {
                continue;
            }

            $collection->updateOne(
                ['order_id' => (int) $row['id']],
                ['$set' => [
                    'order_id' => (int) $row['id'],
                    'menu_id' => (int) $row['menu_id'],
                    'menu_titre' => (string) $row['menu_titre'],
                    'prix_total_centimes' => (int) $row['prix_total_centimes'],
                    'date_commande' => new UTCDateTime($dateCommande->getTimestamp() * 1000),
                ]],
                ['upsert' => true]
            );
        }
    }

    /**
     * @return list<array{menu_id:int, menu_titre:string, commandes:int}>
     */
    public function countOrdersByMenu(): array
    {
        $cursor = $this->collection()->aggregate([
            ['$group' => [
                '_id' => [
                    'menu_id' => '$menu_id',
                    'menu_titre' => '$menu_titre',
                ],
                'commandes' => ['$sum' => 1],
            ]],
            ['$sort' => ['commandes' => -1, '_id.menu_titre' => 1]],
        ]);

        $result = [];
        foreach ($cursor as $doc) {
            $id = (array) ($doc['_id'] ?? []);
            $result[] = [
                'menu_id' => (int) ($id['menu_id'] ?? 0),
                'menu_titre' => (string) ($id['menu_titre'] ?? 'Menu inconnu'),
                'commandes' => (int) ($doc['commandes'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{menu_id:int, menu_titre:string, commandes:int, chiffre_affaires_centimes:int}>
     */
    public function revenueByMenu(?int $menuId, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $match = [];
        if ($menuId !== null) {
            $match['menu_id'] = $menuId;
        }

        $dateFilter = [];
        if ($from instanceof \DateTimeImmutable) {
            $dateFilter['$gte'] = new UTCDateTime($from->getTimestamp() * 1000);
        }
        if ($to instanceof \DateTimeImmutable) {
            // Inclusive end date by moving to next day and using <.
            $toExclusive = $to->setTime(0, 0)->modify('+1 day');
            $dateFilter['$lt'] = new UTCDateTime($toExclusive->getTimestamp() * 1000);
        }
        if ($dateFilter !== []) {
            $match['date_commande'] = $dateFilter;
        }

        $pipeline = [];
        if ($match !== []) {
            $pipeline[] = ['$match' => $match];
        }
        $pipeline[] = ['$group' => [
            '_id' => [
                'menu_id' => '$menu_id',
                'menu_titre' => '$menu_titre',
            ],
            'commandes' => ['$sum' => 1],
            'chiffre_affaires_centimes' => ['$sum' => '$prix_total_centimes'],
        ]];
        $pipeline[] = ['$sort' => ['chiffre_affaires_centimes' => -1, '_id.menu_titre' => 1]];

        $cursor = $this->collection()->aggregate($pipeline);
        $result = [];
        foreach ($cursor as $doc) {
            $id = (array) ($doc['_id'] ?? []);
            $result[] = [
                'menu_id' => (int) ($id['menu_id'] ?? 0),
                'menu_titre' => (string) ($id['menu_titre'] ?? 'Menu inconnu'),
                'commandes' => (int) ($doc['commandes'] ?? 0),
                'chiffre_affaires_centimes' => (int) ($doc['chiffre_affaires_centimes'] ?? 0),
            ];
        }

        return $result;
    }

    private function collection(): Collection
    {
        return $this->mongoClient->selectCollection($this->mongoDbName, self::COLLECTION);
    }
}
