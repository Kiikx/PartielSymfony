<?php

namespace App\Service\Dashboard;

use App\Entity\Activity;
use App\Entity\Incident;
use App\Entity\Transfer;

final readonly class DashboardSummary
{
    /**
     * @param array<string, int> $cellCountsByStatus
     * @param list<DashboardAlert> $alerts
     * @param list<Incident> $recentIncidents
     * @param list<Transfer> $recentTransfers
     * @param list<Activity> $todayActivities
     */
    public function __construct(
        public array $cellCountsByStatus,
        public int $operationalCapacity,
        public int $activeOccupants,
        public float $occupancyRate,
        public int $incarceratedCount,
        public int $openHighSeverityIncidents,
        public array $alerts,
        public array $recentIncidents,
        public array $recentTransfers,
        public array $todayActivities,
    ) {
    }

    public function cellCount(string $status): int
    {
        return $this->cellCountsByStatus[$status] ?? 0;
    }
}
