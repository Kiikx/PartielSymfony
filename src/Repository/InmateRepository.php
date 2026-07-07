<?php

namespace App\Repository;

use App\Entity\Inmate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return list<Inmate>
     */
    public function search(?string $uid, ?string $status, ?string $securityLevel): array
    {
        $qb = $this->createQueryBuilder('inmate')
            ->orderBy('inmate.arrivalDate', 'DESC');

        if ($uid !== null && $uid !== '') {
            $qb->andWhere('inmate.uid LIKE :uid')
                ->setParameter('uid', '%'.mb_strtoupper(trim($uid)).'%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('inmate.status = :status')
                ->setParameter('status', $status);
        }

        if ($securityLevel !== null && $securityLevel !== '') {
            $qb->andWhere('inmate.securityLevel = :securityLevel')
                ->setParameter('securityLevel', $securityLevel);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Incarcerated inmates with no active (ongoing) assignment, eligible for a new one.
     */
    public function createAssignableQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('inmate')
            ->andWhere('inmate.status = :status')
            ->andWhere('NOT EXISTS (
                SELECT 1 FROM App\Entity\Assignment assignment
                WHERE assignment.inmate = inmate AND assignment.endAt IS NULL
            )')
            ->setParameter('status', Inmate::STATUS_INCARCERATED)
            ->orderBy('inmate.lastName', 'ASC');
    }

    /**
     * @return list<Inmate>
     */
    public function findAssignable(): array
    {
        return $this->createAssignableQueryBuilder()->getQuery()->getResult();
    }

    /**
     * Inmates currently holding an active assignment, eligible for a transfer.
     */
    public function createTransferableQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('inmate')
            ->andWhere('EXISTS (
                SELECT 1 FROM App\Entity\Assignment assignment
                WHERE assignment.inmate = inmate AND assignment.endAt IS NULL
            )')
            ->orderBy('inmate.lastName', 'ASC');
    }

    /**
     * @return list<Inmate>
     */
    public function findTransferable(): array
    {
        return $this->createTransferableQueryBuilder()->getQuery()->getResult();
    }
}
