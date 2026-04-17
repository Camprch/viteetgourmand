<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * @return Commande[]
     */
    public function findForEmployeeList(?string $clientSearch = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.menu', 'm')->addSelect('m')
            ->leftJoin('c.communeLivraison', 'cl')->addSelect('cl')
            ->leftJoin('c.commandeStatuts', 'cs')->addSelect('cs')
            ->orderBy('c.dateCommande', 'DESC');

        if ($clientSearch !== null && trim($clientSearch) !== '') {
            $qb
                ->andWhere('c.nomPrenomClient LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . trim($clientSearch) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<array{
     *     id:int,
     *     menu_id:int,
     *     menu_titre:string,
     *     prix_total_centimes:int,
     *     date_commande:\DateTimeImmutable
     * }>
     */
    public function findAllForAnalyticsProjection(): array
    {
        /** @var list<array{
         *     id:int,
         *     menu_id:int,
         *     menu_titre:string,
         *     prix_total_centimes:int,
         *     date_commande:\DateTimeImmutable
         * }> $rows
         */
        $rows = $this->createQueryBuilder('c')
            ->select('c.id AS id')
            ->addSelect('IDENTITY(c.menu) AS menu_id')
            ->addSelect('m.titre AS menu_titre')
            ->addSelect('c.prixTotalCentimes AS prix_total_centimes')
            ->addSelect('c.dateCommande AS date_commande')
            ->leftJoin('c.menu', 'm')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return $rows;
    }

//    /**
//     * @return Commande[] Returns an array of Commande objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Commande
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
