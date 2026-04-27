<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findActiveToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.token = :token')
            ->andWhere('t.usedAt IS NULL')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidateOpenTokensForUser(User $user, \DateTimeImmutable $when): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':when')
            ->where('t.user = :user')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('when', $when)
            ->getQuery()
            ->execute();
    }
}
