<?php

namespace App\Repository;

use App\Entity\Building;
use App\Entity\Cell;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cell>
 */
class CellRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cell::class);
    }

    /**
     * @return array<string, int> counts indexed by cell status
     */
    public function countByStatus(?Building $building = null): array
    {
        $qb = $this->createQueryBuilder('cell')
            ->select('cell.status AS status', 'COUNT(cell.id) AS total')
            ->groupBy('cell.status');

        $this->applyBuildingFilter($qb, $building);

        /** @var list<array{status: string, total: int|string}> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function sumOperationalCapacity(?Building $building = null): int
    {
        $qb = $this->createQueryBuilder('cell')
            ->select('COALESCE(SUM(cell.capacity), 0)')
            ->andWhere('cell.status != :closed')
            ->setParameter('closed', Cell::STATUS_CLOSED);

        $this->applyBuildingFilter($qb, $building);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyBuildingFilter(QueryBuilder $qb, ?Building $building): void
    {
        if ($building === null) {
            return;
        }

        $qb->innerJoin('cell.wing', 'wing')
            ->andWhere('wing.building = :building')
            ->setParameter('building', $building);
    }
}
