<?php

namespace App\Repository;

use App\Entity\Building;
use App\Entity\Incident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findRecentForBuilding(Building $building, int $limit = 10): array
    {
        return $this->createQueryBuilder('incident')
            ->innerJoin('incident.cell', 'cell')
            ->innerJoin('cell.wing', 'wing')
            ->andWhere('wing.building = :building')
            ->setParameter('building', $building)
            ->orderBy('incident.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
