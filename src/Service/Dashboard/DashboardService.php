<?php

namespace App\Service\Dashboard;

use App\Entity\Building;
use App\Entity\Inmate;
use App\Repository\ActivityRepository;
use App\Repository\AssignmentRepository;
use App\Repository\CellRepository;
use App\Repository\IncidentRepository;
use App\Repository\InmateRepository;
use App\Repository\TransferRepository;

final readonly class DashboardService
{
    private const OCCUPANCY_WARNING_THRESHOLD = 75.0;
    private const OCCUPANCY_DANGER_THRESHOLD = 90.0;

    public function __construct(
        private CellRepository $cellRepository,
        private AssignmentRepository $assignmentRepository,
        private InmateRepository $inmateRepository,
        private IncidentRepository $incidentRepository,
        private TransferRepository $transferRepository,
        private ActivityRepository $activityRepository,
    ) {
    }

    public function buildSummary(?Building $building = null): DashboardSummary
    {
        $cellCountsByStatus = $this->cellRepository->countByStatus($building);
        $operationalCapacity = $this->cellRepository->sumOperationalCapacity($building);
        $activeOccupants = $this->assignmentRepository->countActive($building);
        $occupancyRate = $operationalCapacity > 0
            ? round($activeOccupants / $operationalCapacity * 100, 1)
            : 0.0;
        $openHighSeverityIncidents = $this->incidentRepository->countOpenHighSeverity($building);

        return new DashboardSummary(
            cellCountsByStatus: $cellCountsByStatus,
            operationalCapacity: $operationalCapacity,
            activeOccupants: $activeOccupants,
            occupancyRate: $occupancyRate,
            incarceratedCount: $this->inmateRepository->countByStatus(Inmate::STATUS_INCARCERATED),
            openHighSeverityIncidents: $openHighSeverityIncidents,
            alerts: $this->buildAlerts($occupancyRate, $openHighSeverityIncidents),
            recentIncidents: $this->incidentRepository->findRecent($building, 5),
            recentTransfers: $this->transferRepository->findRecent($building, 5),
            todayActivities: $this->activityRepository->findScheduledForDay(new \DateTimeImmutable('today')),
        );
    }

    /**
     * @return list<DashboardAlert>
     */
    private function buildAlerts(float $occupancyRate, int $openHighSeverityIncidents): array
    {
        $alerts = [];

        if ($occupancyRate >= self::OCCUPANCY_DANGER_THRESHOLD) {
            $alerts[] = new DashboardAlert(
                DashboardAlert::LEVEL_DANGER,
                sprintf('Occupation critique : %s%% de la capacite operationnelle est atteinte.', $occupancyRate),
            );
        } elseif ($occupancyRate >= self::OCCUPANCY_WARNING_THRESHOLD) {
            $alerts[] = new DashboardAlert(
                DashboardAlert::LEVEL_WARNING,
                sprintf('Occupation elevee : %s%% de la capacite operationnelle est atteinte.', $occupancyRate),
            );
        }

        if ($openHighSeverityIncidents > 0) {
            $alerts[] = new DashboardAlert(
                DashboardAlert::LEVEL_DANGER,
                sprintf(
                    '%d incident(s) de gravite elevee ou critique en attente de traitement.',
                    $openHighSeverityIncidents,
                ),
            );
        }

        return $alerts;
    }
}
