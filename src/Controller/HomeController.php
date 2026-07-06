<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Assignment;
use App\Entity\AdminUser;
use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\Incident;
use App\Entity\Inmate;
use App\Entity\ManagerUser;
use App\Entity\Notification;
use App\Entity\Transfer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/dashboard', name: 'app_dashboard')]
    public function __invoke(EntityManagerInterface $entityManager): Response
    {
        $databaseReady = true;

        try {
            $metrics = $this->buildMetrics($entityManager);
            $recentIncidents = $this->repository($entityManager, Incident::class)->findBy([], ['occurredAt' => 'DESC'], 5);
            $recentTransfers = $this->repository($entityManager, Transfer::class)->findBy([], ['scheduledAt' => 'DESC'], 5);
            $recentInmates = $this->repository($entityManager, Inmate::class)->findBy([], ['arrivalDate' => 'DESC'], 5);
            $recentNotifications = $this->repository($entityManager, Notification::class)->findBy([], ['sentAt' => 'DESC'], 4);
            $todayActivities = $this->findTodayActivities($entityManager);
            $buildingOccupancy = $this->buildBuildingOccupancy($entityManager);
            $movementStats = $this->buildMovementStats($entityManager);
            $activitySummary = $this->buildActivitySummary($entityManager);
        } catch (\Throwable) {
            $databaseReady = false;
            $metrics = $this->emptyMetrics();
            $recentIncidents = [];
            $recentTransfers = [];
            $recentInmates = [];
            $recentNotifications = [];
            $todayActivities = [];
            $buildingOccupancy = [];
            $movementStats = $this->emptyMovementStats();
            $activitySummary = [];
        }

        return $this->render('dashboard/index.html.twig', [
            'databaseReady' => $databaseReady,
            'metrics' => $metrics,
            'recentIncidents' => $recentIncidents,
            'recentTransfers' => $recentTransfers,
            'recentInmates' => $recentInmates,
            'recentNotifications' => $recentNotifications,
            'todayActivities' => $todayActivities,
            'buildingOccupancy' => $buildingOccupancy,
            'movementStats' => $movementStats,
            'activitySummary' => $activitySummary,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function buildMetrics(EntityManagerInterface $entityManager): array
    {
        $totalCapacity = (int) $entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(cell.capacity), 0)')
            ->from(Cell::class, 'cell')
            ->getQuery()
            ->getSingleScalarResult();

        $activeAssignments = $this->repository($entityManager, Assignment::class)->count(['endAt' => null]);
        $occupancyRate = $totalCapacity > 0 ? (int) round(($activeAssignments / $totalCapacity) * 100) : 0;

        return [
            'totalInmates' => $this->repository($entityManager, Inmate::class)->count([]),
            'activeInmates' => $this->repository($entityManager, Inmate::class)->count(['status' => Inmate::STATUS_INCARCERATED]),
            'activeAssignments' => $activeAssignments,
            'totalCapacity' => $totalCapacity,
            'occupancyRate' => $occupancyRate,
            'staffMembers' => $this->repository($entityManager, GuardUser::class)->count(['isActive' => true])
                + $this->repository($entityManager, ManagerUser::class)->count(['isActive' => true])
                + $this->repository($entityManager, AdminUser::class)->count(['isActive' => true]),
            'openIncidents' => $this->repository($entityManager, Incident::class)->count(['status' => Incident::STATUS_OPEN]),
            'highIncidents' => $this->countHighIncidents($entityManager),
            'todayActivities' => count($this->findTodayActivities($entityManager)),
            'scheduledTransfers' => $this->repository($entityManager, Transfer::class)->count([]),
            'maintenanceCells' => $this->repository($entityManager, Cell::class)->count(['status' => Cell::STATUS_MAINTENANCE]),
            'fullCells' => $this->repository($entityManager, Cell::class)->count(['status' => Cell::STATUS_FULL]),
        ];
    }

    /**
     * @return list<array{code: string, name: string, capacity: int, occupied: int, rate: int}>
     */
    private function buildBuildingOccupancy(EntityManagerInterface $entityManager): array
    {
        $rows = $entityManager->createQueryBuilder()
            ->select('building.code AS code')
            ->addSelect('building.name AS name')
            ->addSelect('COALESCE(SUM(cell.capacity), 0) AS capacity')
            ->addSelect('COUNT(DISTINCT assignment.id) AS occupied')
            ->from(Building::class, 'building')
            ->leftJoin('building.wings', 'wing')
            ->leftJoin('wing.cells', 'cell')
            ->leftJoin('cell.assignments', 'assignment', 'WITH', 'assignment.endAt IS NULL')
            ->groupBy('building.id')
            ->addGroupBy('building.code')
            ->addGroupBy('building.name')
            ->orderBy('building.code', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $row): array {
            $capacity = (int) $row['capacity'];
            $occupied = (int) $row['occupied'];

            return [
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'capacity' => $capacity,
                'occupied' => $occupied,
                'rate' => $capacity > 0 ? (int) round(($occupied / $capacity) * 100) : 0,
            ];
        }, $rows);
    }

    /**
     * @return array{total: int, internal: int, external: int, activities: int, assignments: int}
     */
    private function buildMovementStats(EntityManagerInterface $entityManager): array
    {
        $internalTransfers = $this->repository($entityManager, Transfer::class)->count(['type' => Transfer::TYPE_INTERNAL]);
        $externalTransfers = $this->repository($entityManager, Transfer::class)->count(['type' => Transfer::TYPE_EXTERNAL]);
        $activities = $this->repository($entityManager, Activity::class)->count([]);
        $assignments = $this->repository($entityManager, Assignment::class)->count([]);

        return [
            'total' => $internalTransfers + $externalTransfers + $activities + $assignments,
            'internal' => $internalTransfers,
            'external' => $externalTransfers,
            'activities' => $activities,
            'assignments' => $assignments,
        ];
    }

    /**
     * @return list<array{type: string, total: int}>
     */
    private function buildActivitySummary(EntityManagerInterface $entityManager): array
    {
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        return $entityManager->createQueryBuilder()
            ->select('activity.type AS type')
            ->addSelect('COUNT(activity.id) AS total')
            ->from(Activity::class, 'activity')
            ->where('activity.scheduledAt >= :start')
            ->andWhere('activity.scheduledAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('activity.type')
            ->orderBy('total', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getArrayResult();
    }

    private function countHighIncidents(EntityManagerInterface $entityManager): int
    {
        return (int) $entityManager->createQueryBuilder()
            ->select('COUNT(incident.id)')
            ->from(Incident::class, 'incident')
            ->where('incident.severity IN (:severities)')
            ->setParameter('severities', [Incident::SEVERITY_HIGH, Incident::SEVERITY_CRITICAL])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Activity>
     */
    private function findTodayActivities(EntityManagerInterface $entityManager): array
    {
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        return $entityManager->createQueryBuilder()
            ->select('activity')
            ->from(Activity::class, 'activity')
            ->where('activity.scheduledAt >= :start')
            ->andWhere('activity.scheduledAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('activity.scheduledAt', 'ASC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    private function emptyMetrics(): array
    {
        return [
            'totalInmates' => 0,
            'activeInmates' => 0,
            'activeAssignments' => 0,
            'totalCapacity' => 0,
            'occupancyRate' => 0,
            'staffMembers' => 0,
            'openIncidents' => 0,
            'highIncidents' => 0,
            'todayActivities' => 0,
            'scheduledTransfers' => 0,
            'maintenanceCells' => 0,
            'fullCells' => 0,
        ];
    }

    /**
     * @return array{total: int, internal: int, external: int, activities: int, assignments: int}
     */
    private function emptyMovementStats(): array
    {
        return [
            'total' => 0,
            'internal' => 0,
            'external' => 0,
            'activities' => 0,
            'assignments' => 0,
        ];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return EntityRepository<T>
     */
    private function repository(EntityManagerInterface $entityManager, string $className): EntityRepository
    {
        return $entityManager->getRepository($className);
    }
}
