<?php

namespace App\Tests\Service;

use App\Entity\Activity;
use App\Entity\ActivityParticipation;
use App\Entity\AuditLog;
use App\Entity\GuardUser;
use App\Entity\Inmate;
use App\Entity\User;
use App\Repository\ActivityParticipationRepository;
use App\Service\ActivityParticipationService;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ActivityParticipationServiceTest extends TestCase
{
    public function testRecordParticipationCreatesNewParticipationAndAuditLog(): void
    {
        $inmate = $this->createInmate();
        $activity = $this->createActivity();
        $actor = $this->createActor();
        $persisted = [];

        $repository = $this->createStub(ActivityParticipationRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $callback) use ($entityManager): mixed {
                return $callback($entityManager);
            });
        $entityManager
            ->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $participation = (new ActivityParticipationService($repository, $entityManager, new AuditLogger()))
            ->recordParticipation($activity, $inmate, ActivityParticipation::STATUS_PRESENT, $actor);

        self::assertSame($inmate, $participation->getInmate());
        self::assertSame($activity, $participation->getActivity());
        self::assertSame(ActivityParticipation::STATUS_PRESENT, $participation->getStatus());
        self::assertSame($actor, $participation->getCheckedBy());
        self::assertNotNull($participation->getCheckedAt());
        self::assertSame($participation, $persisted[0]);
        self::assertInstanceOf(AuditLog::class, $persisted[1]);
        self::assertSame('ACTIVITY_PARTICIPATION_RECORDED', $persisted[1]->getAction());
    }

    public function testRecordParticipationUpdatesExistingParticipation(): void
    {
        $inmate = $this->createInmate();
        $activity = $this->createActivity();
        $actor = $this->createActor();
        $existing = (new ActivityParticipation())->setActivity($activity)->setInmate($inmate)->setStatus(ActivityParticipation::STATUS_ABSENT);

        $repository = $this->createStub(ActivityParticipationRepository::class);
        $repository->method('findOneBy')->willReturn($existing);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $callback) use ($entityManager): mixed {
                return $callback($entityManager);
            });

        $participation = (new ActivityParticipationService($repository, $entityManager, new AuditLogger()))
            ->recordParticipation($activity, $inmate, ActivityParticipation::STATUS_PRESENT, $actor);

        self::assertSame($existing, $participation);
        self::assertSame(ActivityParticipation::STATUS_PRESENT, $participation->getStatus());
    }

    private function createInmate(): Inmate
    {
        return (new Inmate())
            ->setUid('D-001')
            ->setFirstName('Jean')
            ->setLastName('Dupont')
            ->setBirthDate(new \DateTimeImmutable('1985-01-01'))
            ->setArrivalDate(new \DateTimeImmutable('2026-01-01'));
    }

    private function createActivity(): Activity
    {
        return (new Activity())
            ->setType(Activity::TYPE_CANTEEN)
            ->setLabel('Cantine du midi');
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
