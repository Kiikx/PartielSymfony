<?php

namespace App\Repository;

use App\Entity\Building;
use App\Entity\Incident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Incident>
 */
class IncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Incident::class);
    }

    /**
     * @return list<Incident>
     */
    public function findRecent(?Building $building = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('incident')
            ->orderBy('incident.occurredAt', 'DESC')
            ->setMaxResults($limit);

        $this->applyBuildingFilter($qb, $building);

        return $qb->getQuery()->getResult();
    }

    public function countOpenHighSeverity(?Building $building = null): int
    {
        $qb = $this->createQueryBuilder('incident')
            ->select('COUNT(incident.id)')
            ->andWhere('incident.status IN (:openStatuses)')
            ->andWhere('incident.severity IN (:severities)')
            ->setParameter('openStatuses', [Incident::STATUS_OPEN, Incident::STATUS_PROCESSING])
            ->setParameter('severities', [Incident::SEVERITY_HIGH, Incident::SEVERITY_CRITICAL]);

        $this->applyBuildingFilter($qb, $building);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyBuildingFilter(QueryBuilder $qb, ?Building $building): void
    {
        if ($building === null) {
            return;
        }

        $qb->innerJoin('incident.cell', 'cell')
            ->innerJoin('cell.wing', 'wing')
            ->andWhere('wing.building = :building')
            ->setParameter('building', $building);
    }
}
