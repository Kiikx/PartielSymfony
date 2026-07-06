<?php

namespace App\Tests\Service;

use App\Entity\Assignment;
use App\Entity\AuditLog;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\Inmate;
use App\Entity\User;
use App\Exception\AssignmentException;
use App\Repository\AssignmentRepository;
use App\Service\AssignmentService;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AssignmentServiceTest extends TestCase
{
    public function testAssignCreatesAssignmentAndAuditLog(): void
    {
        $inmate = $this->createInmate();
        $cell = $this->createCell(capacity: 2);
        $actor = $this->createActor();
        $persisted = [];

        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn(null);
        $repository->method('countActiveAssignmentsForCell')->with($cell)->willReturn(1);

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
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $assignment = (new AssignmentService($repository, $entityManager, new AuditLogger()))
            ->assign($inmate, $cell, $actor, 'Arrivee initiale');

        self::assertSame($inmate, $assignment->getInmate());
        self::assertSame($cell, $assignment->getCell());
        self::assertSame($actor, $assignment->getCreatedBy());
        self::assertSame('Arrivee initiale', $assignment->getReason());
        self::assertSame($assignment, $persisted[0]);
        self::assertInstanceOf(AuditLog::class, $persisted[1]);
        self::assertSame('ASSIGNMENT_CREATED', $persisted[1]->getAction());
        self::assertSame([
            'cell' => 'A-101',
            'inmate' => 'D-001',
        ], $persisted[1]->getDetails());
    }

    public function testAssignRejectsReleasedInmate(): void
    {
        $service = new AssignmentService(
            $this->createStub(AssignmentRepository::class),
            $this->createStub(EntityManagerInterface::class),
            new AuditLogger(),
        );

        $this->expectException(AssignmentException::class);

        $service->assign(
            $this->createInmate(status: Inmate::STATUS_RELEASED),
            $this->createCell(),
            $this->createActor(),
        );
    }

    public function testAssignRejectsInmateWithActiveAssignment(): void
    {
        $inmate = $this->createInmate();
        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn(new Assignment());

        $service = new AssignmentService(
            $repository,
            $this->createStub(EntityManagerInterface::class),
            new AuditLogger(),
        );

        $this->expectException(AssignmentException::class);

        $service->assign($inmate, $this->createCell(), $this->createActor());
    }

    public function testAssignRejectsFullCell(): void
    {
        $inmate = $this->createInmate();
        $cell = $this->createCell(capacity: 1);
        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn(null);
        $repository->method('countActiveAssignmentsForCell')->with($cell)->willReturn(1);

        $service = new AssignmentService(
            $repository,
            $this->createStub(EntityManagerInterface::class),
            new AuditLogger(),
        );

        $this->expectException(AssignmentException::class);

        $service->assign($inmate, $cell, $this->createActor());
    }

    private function createInmate(string $status = Inmate::STATUS_INCARCERATED): Inmate
    {
        return (new Inmate())
            ->setUid('D-001')
            ->setFirstName('Jean')
            ->setLastName('Dupont')
            ->setBirthDate(new \DateTimeImmutable('1985-01-01'))
            ->setArrivalDate(new \DateTimeImmutable('2026-01-01'))
            ->setStatus($status);
    }

    private function createCell(int $capacity = 2): Cell
    {
        return (new Cell())
            ->setNumber('A-101')
            ->setCapacity($capacity);
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
