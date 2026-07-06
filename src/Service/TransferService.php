<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\Cell;
use App\Entity\Inmate;
use App\Entity\Transfer;
use App\Entity\User;
use App\Exception\TransferException;
use App\Repository\AssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TransferService
{
    public function __construct(
        private readonly AssignmentRepository $assignmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function transferInternally(
        Inmate $inmate,
        Cell $targetCell,
        User $actor,
        string $reason,
        ?\DateTimeImmutable $scheduledAt = null,
    ): Transfer {
        $activeAssignment = $this->getRequiredActiveAssignment($inmate);
        $sourceCell = $activeAssignment->getCell();

        if ($sourceCell === $targetCell) {
            throw TransferException::targetCellMustBeDifferent($targetCell);
        }

        $this->assertTargetCellHasCapacity($targetCell);

        $transfer = $this->createTransfer(
            $inmate,
            Transfer::TYPE_INTERNAL,
            $reason,
            $actor,
            $scheduledAt,
            $sourceCell,
            $targetCell,
        );

        $newAssignment = (new Assignment())
            ->setInmate($inmate)
            ->setCell($targetCell)
            ->setReason('Transfert interne: '.$reason)
            ->setCreatedBy($actor);

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($activeAssignment, $actor, $newAssignment, $targetCell, $transfer): void {
            $activeAssignment->setEndAt(new \DateTimeImmutable());
            $entityManager->persist($transfer);
            $entityManager->persist($newAssignment);
            $entityManager->flush();

            $entityManager->persist($this->auditLogger->create($actor, 'TRANSFER_INTERNAL_CREATED', $transfer, [
                'fromCell' => $transfer->getFromCell()?->getNumber(),
                'toCell' => $targetCell->getNumber(),
            ]));
        });

        return $transfer;
    }

    public function transferExternally(
        Inmate $inmate,
        string $externalDestination,
        User $actor,
        string $reason,
        ?\DateTimeImmutable $scheduledAt = null,
    ): Transfer {
        $externalDestination = trim($externalDestination);
        if ($externalDestination === '') {
            throw TransferException::externalDestinationRequired();
        }

        $activeAssignment = $this->getRequiredActiveAssignment($inmate);

        $transfer = $this->createTransfer(
            $inmate,
            Transfer::TYPE_EXTERNAL,
            $reason,
            $actor,
            $scheduledAt,
            $activeAssignment->getCell(),
            null,
            $externalDestination,
        );

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($activeAssignment, $actor, $externalDestination, $inmate, $transfer): void {
            $activeAssignment->setEndAt(new \DateTimeImmutable());
            $inmate->setStatus(Inmate::STATUS_EXTERNAL_TRANSFER);
            $entityManager->persist($transfer);
            $entityManager->flush();

            $entityManager->persist($this->auditLogger->create($actor, 'TRANSFER_EXTERNAL_CREATED', $transfer, [
                'fromCell' => $transfer->getFromCell()?->getNumber(),
                'destination' => $externalDestination,
            ]));
        });

        return $transfer;
    }

    private function getRequiredActiveAssignment(Inmate $inmate): Assignment
    {
        $activeAssignment = $this->assignmentRepository->findActiveForInmate($inmate);
        if (!$activeAssignment instanceof Assignment) {
            throw TransferException::activeAssignmentRequired($inmate);
        }

        return $activeAssignment;
    }

    private function assertTargetCellHasCapacity(Cell $targetCell): void
    {
        if ($this->assignmentRepository->countActiveAssignmentsForCell($targetCell) >= $targetCell->getCapacity()) {
            throw TransferException::targetCellIsFull($targetCell);
        }
    }

    private function createTransfer(
        Inmate $inmate,
        string $type,
        string $reason,
        User $actor,
        ?\DateTimeImmutable $scheduledAt,
        ?Cell $fromCell,
        ?Cell $toCell = null,
        ?string $externalDestination = null,
    ): Transfer {
        return (new Transfer())
            ->setInmate($inmate)
            ->setType($type)
            ->setReason($reason)
            ->setValidatedBy($actor)
            ->setScheduledAt($scheduledAt ?? new \DateTimeImmutable())
            ->setFromCell($fromCell)
            ->setToCell($toCell)
            ->setExternalDestination($externalDestination);
    }
}
