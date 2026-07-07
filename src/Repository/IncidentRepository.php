<?php

namespace App\Repository;

use App\Entity\AdminUser;
use App\Entity\Building;
use App\Entity\GuardUser;
use App\Entity\Incident;
use App\Entity\ManagerUser;
use App\Entity\User;
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
    public function searchForUser(User $viewer, array $filters, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('incident')
            ->addSelect('cell', 'wing', 'reportedBy')
            ->leftJoin('incident.cell', 'cell')
            ->leftJoin('cell.wing', 'wing')
            ->leftJoin('incident.reportedBy', 'reportedBy')
            ->orderBy('incident.occurredAt', 'DESC')
            ->setMaxResults($limit);

        $this->applyVisibilityFilter($qb, $viewer);
        $this->applySearchFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{text?: mixed, status?: mixed, severity?: mixed, cell?: mixed} $filters
     */
    public function countForUser(User $viewer, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('incident')
            ->select('COUNT(DISTINCT incident.id)')
            ->leftJoin('incident.cell', 'cell')
            ->leftJoin('cell.wing', 'wing');

        $this->applyVisibilityFilter($qb, $viewer);
        $this->applySearchFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
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

    private function applyVisibilityFilter(QueryBuilder $qb, User $viewer): void
    {
        if ($viewer instanceof AdminUser) {
            return;
        }

        if ($viewer instanceof ManagerUser) {
            $managedBuilding = $viewer->getManagedBuilding();
            if ($managedBuilding === null) {
                $qb->andWhere('1 = 0');

                return;
            }

            $qb
                ->andWhere('wing.building = :viewerBuilding')
                ->setParameter('viewerBuilding', $managedBuilding);

            return;
        }

        if ($viewer instanceof GuardUser) {
            $assignedZone = $viewer->getAssignedZone();
            if ($assignedZone === null) {
                $qb
                    ->andWhere('incident.reportedBy = :viewer')
                    ->setParameter('viewer', $viewer);

                return;
            }

            $qb
                ->andWhere('incident.reportedBy = :viewer OR wing = :assignedZone')
                ->setParameter('viewer', $viewer)
                ->setParameter('assignedZone', $assignedZone);

            return;
        }

        $qb->andWhere('1 = 0');
    }

    /**
     * @param array{text?: mixed, status?: mixed, severity?: mixed, cell?: mixed} $filters
     */
    private function applySearchFilters(QueryBuilder $qb, array $filters): void
    {
        $text = is_string($filters['text'] ?? null) ? trim($filters['text']) : '';
        if ($text !== '') {
            $qb
                ->andWhere('LOWER(incident.title) LIKE :incidentText OR LOWER(incident.description) LIKE :incidentText')
                ->setParameter('incidentText', '%'.mb_strtolower($text).'%');
        }

        $status = $filters['status'] ?? null;
        if (is_string($status) && in_array($status, Incident::STATUSES, true)) {
            $qb
                ->andWhere('incident.status = :incidentStatus')
                ->setParameter('incidentStatus', $status);
        }

        $severity = $filters['severity'] ?? null;
        if (is_string($severity) && in_array($severity, Incident::SEVERITIES, true)) {
            $qb
                ->andWhere('incident.severity = :incidentSeverity')
                ->setParameter('incidentSeverity', $severity);
        }

        $cell = $filters['cell'] ?? null;
        if (is_numeric($cell) && (int) $cell > 0) {
            $qb
                ->andWhere('cell.id = :incidentCell')
                ->setParameter('incidentCell', (int) $cell);
        }
    }
}
