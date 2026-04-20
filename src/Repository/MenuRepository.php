<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * @param array{theme?: string, regime?: string, personnes_min?: int|null, prix_min_centimes?: int|null, prix_max_centimes?: int|null} $filters
     * @return list<Menu>
     */
    public function findActiveFiltered(array $filters): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('m.createdAt', 'DESC');

        if (($filters['theme'] ?? '') !== '') {
            $qb->andWhere('m.theme = :theme')
                ->setParameter('theme', (string) $filters['theme']);
        }

        if (($filters['regime'] ?? '') !== '') {
            $qb->andWhere('m.regime = :regime')
                ->setParameter('regime', (string) $filters['regime']);
        }

        if (($filters['personnes_min'] ?? null) !== null) {
            // Show menus compatible with the requested group size.
            $qb->andWhere('m.personnesMin <= :personnesMin')
                ->setParameter('personnesMin', (int) $filters['personnes_min']);
        }

        if (($filters['prix_min_centimes'] ?? null) !== null) {
            $qb->andWhere('m.prixMinCentimes >= :prixMinCentimes')
                ->setParameter('prixMinCentimes', (int) $filters['prix_min_centimes']);
        }

        if (($filters['prix_max_centimes'] ?? null) !== null) {
            $qb->andWhere('m.prixMinCentimes <= :prixMaxCentimes')
                ->setParameter('prixMaxCentimes', (int) $filters['prix_max_centimes']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<string>
     */
    public function findActiveThemes(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['theme'],
            $this->createQueryBuilder('m')
                ->select('DISTINCT m.theme AS theme')
                ->andWhere('m.actif = :actif')
                ->setParameter('actif', true)
                ->orderBy('m.theme', 'ASC')
                ->getQuery()
                ->getArrayResult()
        );
    }

    /**
     * @return list<string>
     */
    public function findActiveRegimes(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['regime'],
            $this->createQueryBuilder('m')
                ->select('DISTINCT m.regime AS regime')
                ->andWhere('m.actif = :actif')
                ->setParameter('actif', true)
                ->orderBy('m.regime', 'ASC')
                ->getQuery()
                ->getArrayResult()
        );
    }

//    /**
//     * @return Menu[] Returns an array of Menu objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Menu
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
