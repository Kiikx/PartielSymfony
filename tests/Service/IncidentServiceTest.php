<?php

namespace App\Tests\Service;

use App\Entity\AuditLog;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\Incident;
use App\Entity\Notification;
use App\Entity\User;
use App\Service\AuditLogger;
use App\Service\IncidentNotificationCreatorInterface;
use App\Service\IncidentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class IncidentServiceTest extends TestCase
{
    public function testCreatePersistsIncidentNotificationsAndAuditLog(): void
    {
        $actor = $this->createActor();
        $notification = new Notification();
        $persisted = [];
        $entityManager = $this->createTransactionalEntityManager($persisted);
        $notificationCreator = $this->createMock(IncidentNotificationCreatorInterface::class);
        $notificationCreator
            ->expects(self::once())
            ->method('createHighSeverityIncidentNotifications')
            ->with(self::isInstanceOf(Incident::class))
            ->willReturn([$notification]);

        $incident = (new IncidentService($entityManager, new AuditLogger(), $notificationCreator))
            ->create('Incident grave', 'Description', Incident::SEVERITY_HIGH, $actor, new Cell());

        self::assertSame('Incident grave', $incident->getTitle());
        self::assertSame($actor, $incident->getReportedBy());
        self::assertSame(Incident::STATUS_OPEN, $incident->getStatus());
        self::assertSame($incident, $persisted[0]);
        self::assertSame($notification, $persisted[1]);
        self::assertInstanceOf(AuditLog::class, $persisted[2]);
        self::assertSame('INCIDENT_CREATED', $persisted[2]->getAction());
    }

    public function testCloseUpdatesStatusAndCreatesAuditLog(): void
    {
        $actor = $this->createActor();
        $incident = (new Incident())
            ->setTitle('Incident test')
            ->setDescription('Description')
            ->setSeverity(Incident::SEVERITY_MEDIUM)
            ->setStatus(Incident::STATUS_OPEN);
        $persisted = [];
        $entityManager = $this->createTransactionalEntityManager($persisted);

        (new IncidentService($entityManager, new AuditLogger(), $this->createStub(IncidentNotificationCreatorInterface::class)))
            ->close($incident, $actor);

        self::assertSame(Incident::STATUS_CLOSED, $incident->getStatus());
        self::assertSame($incident, $persisted[0]);
        self::assertInstanceOf(AuditLog::class, $persisted[1]);
        self::assertSame('INCIDENT_CLOSED', $persisted[1]->getAction());
    }

    /**
     * @param list<object> $persisted
     */
    private function createTransactionalEntityManager(array &$persisted): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $callback) use ($entityManager): mixed {
                return $callback($entityManager);
            });
        $entityManager
            ->expects(self::atLeastOnce())
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager
            ->expects(self::once())
            ->method('flush');

        return $entityManager;
    }

    private function createActor(): User
    {
        $actor = new GuardUser();
        $actor
            ->setEmail('guard@pas.test')
            ->setFirstName('Ness')
            ->setLastName('Cake');
        $actor->setBadgeNumber('PAS-G-001');

        return $actor;
    }
}
