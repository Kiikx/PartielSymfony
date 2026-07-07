<?php

namespace App\Repository;

use App\Entity\Assignment;
use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\Inmate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assignment>
 */
class AssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    public function findActiveForInmate(Inmate $inmate): ?Assignment
    {
        return $this->createQueryBuilder('assignment')
            ->andWhere('assignment.inmate = :inmate')
            ->andWhere('assignment.endAt IS NULL')
            ->setParameter('inmate', $inmate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActiveAssignmentsForCell(Cell $cell): int
    {
        return (int) $this->createQueryBuilder('assignment')
            ->select('COUNT(assignment.id)')
            ->andWhere('assignment.cell = :cell')
            ->andWhere('assignment.endAt IS NULL')
            ->setParameter('cell', $cell)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActive(?Building $building = null): int
    {
        $qb = $this->createQueryBuilder('assignment')
            ->select('COUNT(assignment.id)')
            ->andWhere('assignment.endAt IS NULL');

        if ($building !== null) {
            $qb->innerJoin('assignment.cell', 'cell')
                ->innerJoin('cell.wing', 'wing')
                ->andWhere('wing.building = :building')
                ->setParameter('building', $building);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
