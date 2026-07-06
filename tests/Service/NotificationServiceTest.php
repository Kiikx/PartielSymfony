<?php

namespace App\Tests\Service;

use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\Incident;
use App\Entity\ManagerUser;
use App\Entity\Wing;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;

final class NotificationServiceTest extends TestCase
{
    public function testCreatesNotificationsForHighSeverityIncidentManagers(): void
    {
        $building = $this->createBuilding();
        $incident = $this->createIncident($building, Incident::SEVERITY_HIGH);
        $manager = $this->createManager($building);
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findActiveManagersForBuilding')->with($building)->willReturn([$manager]);

        $notifications = (new NotificationService($repository))->createHighSeverityIncidentNotifications($incident);

        self::assertCount(1, $notifications);
        self::assertSame($manager, $notifications[0]->getRecipient());
        self::assertSame('Incident ELEVEE: Incident test', $notifications[0]->getSubject());
    }

    public function testDoesNotNotifyManagersForLowSeverityIncident(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('findActiveManagersForBuilding');

        $notifications = (new NotificationService($repository))
            ->createHighSeverityIncidentNotifications($this->createIncident($this->createBuilding(), Incident::SEVERITY_LOW));

        self::assertSame([], $notifications);
    }

    private function createIncident(Building $building, string $severity): Incident
    {
        $wing = (new Wing())
            ->setName('Aile test')
            ->setFloor(1)
            ->setBuilding($building);
        $cell = (new Cell())
            ->setNumber('A-101')
            ->setCapacity(2)
            ->setWing($wing);

        return (new Incident())
            ->setTitle('Incident test')
            ->setDescription('Description test')
            ->setSeverity($severity)
            ->setCell($cell);
    }

    private function createBuilding(): Building
    {
        return (new Building())
            ->setName('Batiment A')
            ->setCode('A');
    }

    private function createManager(Building $building): ManagerUser
    {
        $manager = new ManagerUser();
        $manager
            ->setEmail('manager@pas.test')
            ->setFirstName('Kiki')
            ->setLastName('White');
        $manager->setManagedBuilding($building);

        return $manager;
    }
}
