<?php

namespace App\Repository;

use App\Entity\Inmate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inmate>
 */
class InmateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inmate::class);
    }

    public function findOneByUid(string $uid): ?Inmate
    {
        return $this->createQueryBuilder('inmate')
            ->andWhere('inmate.uid = :uid')
            ->setParameter('uid', mb_strtoupper(trim($uid)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('inmate')
            ->select('COUNT(inmate.id)')
            ->andWhere('inmate.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
