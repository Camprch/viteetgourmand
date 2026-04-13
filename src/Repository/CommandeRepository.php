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
