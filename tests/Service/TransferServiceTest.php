<?php

namespace App\Tests\Service;

use App\Entity\Assignment;
use App\Entity\AuditLog;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\Inmate;
use App\Entity\Transfer;
use App\Entity\User;
use App\Exception\TransferException;
use App\Repository\AssignmentRepository;
use App\Service\AuditLogger;
use App\Service\TransferService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TransferServiceTest extends TestCase
{
    public function testTransferInternallyClosesSourceAssignmentAndCreatesTargetAssignment(): void
    {
        $inmate = $this->createInmate();
        $actor = $this->createActor();
        $sourceCell = $this->createCell('A-101');
        $targetCell = $this->createCell('B-201', 2);
        $activeAssignment = $this->createAssignment($inmate, $sourceCell);
        $persisted = [];

        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn($activeAssignment);
        $repository->method('countActiveAssignmentsForCell')->with($targetCell)->willReturn(1);

        $entityManager = $this->createTransactionalEntityManager($persisted);

        $transfer = (new TransferService($repository, $entityManager, new AuditLogger()))
            ->transferInternally($inmate, $targetCell, $actor, 'Changement aile');

        self::assertSame(Transfer::TYPE_INTERNAL, $transfer->getType());
        self::assertSame($sourceCell, $transfer->getFromCell());
        self::assertSame($targetCell, $transfer->getToCell());
        self::assertNotNull($activeAssignment->getEndAt());
        self::assertSame($transfer, $persisted[0]);
        self::assertInstanceOf(Assignment::class, $persisted[1]);
        self::assertSame($targetCell, $persisted[1]->getCell());
        self::assertInstanceOf(AuditLog::class, $persisted[2]);
        self::assertSame('TRANSFER_INTERNAL_CREATED', $persisted[2]->getAction());
    }

    public function testTransferExternallyClosesAssignmentAndUpdatesInmateStatus(): void
    {
        $inmate = $this->createInmate();
        $actor = $this->createActor();
        $sourceCell = $this->createCell('A-101');
        $activeAssignment = $this->createAssignment($inmate, $sourceCell);
        $persisted = [];

        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn($activeAssignment);

        $entityManager = $this->createTransactionalEntityManager($persisted);

        $transfer = (new TransferService($repository, $entityManager, new AuditLogger()))
            ->transferExternally($inmate, 'Centre externe Nord', $actor, 'Extraction definitive');

        self::assertSame(Transfer::TYPE_EXTERNAL, $transfer->getType());
        self::assertSame($sourceCell, $transfer->getFromCell());
        self::assertNull($transfer->getToCell());
        self::assertSame('Centre externe Nord', $transfer->getExternalDestination());
        self::assertSame(Inmate::STATUS_EXTERNAL_TRANSFER, $inmate->getStatus());
        self::assertNotNull($activeAssignment->getEndAt());
        self::assertSame($transfer, $persisted[0]);
        self::assertInstanceOf(AuditLog::class, $persisted[1]);
        self::assertSame('TRANSFER_EXTERNAL_CREATED', $persisted[1]->getAction());
    }

    public function testTransferRequiresActiveAssignment(): void
    {
        $inmate = $this->createInmate();
        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn(null);

        $service = new TransferService(
            $repository,
            $this->createStub(EntityManagerInterface::class),
            new AuditLogger(),
        );

        $this->expectException(TransferException::class);

        $service->transferInternally($inmate, $this->createCell('B-201'), $this->createActor(), 'Motif');
    }

    public function testInternalTransferRejectsFullTargetCell(): void
    {
        $inmate = $this->createInmate();
        $sourceCell = $this->createCell('A-101');
        $targetCell = $this->createCell('B-201', 1);
        $repository = $this->createMock(AssignmentRepository::class);
        $repository->method('findActiveForInmate')->with($inmate)->willReturn($this->createAssignment($inmate, $sourceCell));
        $repository->method('countActiveAssignmentsForCell')->with($targetCell)->willReturn(1);

        $service = new TransferService(
            $repository,
            $this->createStub(EntityManagerInterface::class),
            new AuditLogger(),
        );

        $this->expectException(TransferException::class);

        $service->transferInternally($inmate, $targetCell, $this->createActor(), 'Motif');
    }

    public function testExternalTransferRequiresDestination(): void
    {
        $service = new TransferService(
            $this->createStub(AssignmentRepository::class),
            $this->createStub(EntityManagerInterface::class),
            new AuditLogger(),
        );

        $this->expectException(TransferException::class);

        $service->transferExternally($this->createInmate(), ' ', $this->createActor(), 'Motif');
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

    private function createAssignment(Inmate $inmate, Cell $cell): Assignment
    {
        return (new Assignment())
            ->setInmate($inmate)
            ->setCell($cell);
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

    private function createCell(string $number, int $capacity = 2): Cell
    {
        return (new Cell())
            ->setNumber($number)
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
