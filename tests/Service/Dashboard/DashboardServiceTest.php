<?php

namespace App\Tests\Service\Dashboard;

use App\Entity\Cell;
use App\Repository\ActivityRepository;
use App\Repository\AssignmentRepository;
use App\Repository\CellRepository;
use App\Repository\IncidentRepository;
use App\Repository\InmateRepository;
use App\Repository\TransferRepository;
use App\Service\Dashboard\DashboardAlert;
use App\Service\Dashboard\DashboardService;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
    public function testBuildSummaryComputesOccupancyRate(): void
    {
        $summary = $this->createService(capacity: 40, activeOccupants: 10)->buildSummary();

        self::assertSame(25.0, $summary->occupancyRate);
        self::assertSame(40, $summary->operationalCapacity);
        self::assertSame(10, $summary->activeOccupants);
        self::assertSame([], $summary->alerts);
    }

    public function testBuildSummaryHandlesZeroCapacity(): void
    {
        $summary = $this->createService(capacity: 0, activeOccupants: 0)->buildSummary();

        self::assertSame(0.0, $summary->occupancyRate);
        self::assertSame([], $summary->alerts);
    }

    public function testBuildSummaryAddsWarningAlertOnHighOccupancy(): void
    {
        $summary = $this->createService(capacity: 100, activeOccupants: 80)->buildSummary();

        self::assertCount(1, $summary->alerts);
        self::assertSame(DashboardAlert::LEVEL_WARNING, $summary->alerts[0]->level);
    }

    public function testBuildSummaryAddsDangerAlertsOnCriticalOccupancyAndOpenIncidents(): void
    {
        $summary = $this->createService(capacity: 100, activeOccupants: 95, openHighSeverityIncidents: 2)
            ->buildSummary();

        self::assertCount(2, $summary->alerts);
        self::assertSame(DashboardAlert::LEVEL_DANGER, $summary->alerts[0]->level);
        self::assertSame(DashboardAlert::LEVEL_DANGER, $summary->alerts[1]->level);
        self::assertSame(2, $summary->openHighSeverityIncidents);
    }

    public function testCellCountFallsBackToZeroForMissingStatus(): void
    {
        $summary = $this->createService(capacity: 10, activeOccupants: 2)->buildSummary();

        self::assertSame(3, $summary->cellCount(Cell::STATUS_AVAILABLE));
        self::assertSame(0, $summary->cellCount(Cell::STATUS_MAINTENANCE));
    }

    private function createService(
        int $capacity,
        int $activeOccupants,
        int $openHighSeverityIncidents = 0,
    ): DashboardService {
        $cellRepository = $this->createStub(CellRepository::class);
        $cellRepository->method('countByStatus')->willReturn([Cell::STATUS_AVAILABLE => 3]);
        $cellRepository->method('sumOperationalCapacity')->willReturn($capacity);

        $assignmentRepository = $this->createStub(AssignmentRepository::class);
        $assignmentRepository->method('countActive')->willReturn($activeOccupants);

        $inmateRepository = $this->createStub(InmateRepository::class);
        $inmateRepository->method('countByStatus')->willReturn($activeOccupants);

        $incidentRepository = $this->createStub(IncidentRepository::class);
        $incidentRepository->method('countOpenHighSeverity')->willReturn($openHighSeverityIncidents);
        $incidentRepository->method('findRecent')->willReturn([]);

        $transferRepository = $this->createStub(TransferRepository::class);
        $transferRepository->method('findRecent')->willReturn([]);

        $activityRepository = $this->createStub(ActivityRepository::class);
        $activityRepository->method('findScheduledForDay')->willReturn([]);

        return new DashboardService(
            $cellRepository,
            $assignmentRepository,
            $inmateRepository,
            $incidentRepository,
            $transferRepository,
            $activityRepository,
        );
    }
}
