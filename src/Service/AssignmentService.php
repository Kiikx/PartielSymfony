<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\Cell;
use App\Entity\Inmate;
use App\Entity\User;
use App\Exception\AssignmentException;
use App\Repository\AssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AssignmentService
{
    public function __construct(
        private readonly AssignmentRepository $assignmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function assign(Inmate $inmate, Cell $cell, User $actor, ?string $reason = null): Assignment
    {
        $this->assertInmateCanBeAssigned($inmate);
        $this->assertInmateHasNoActiveAssignment($inmate);
        $this->assertCellHasCapacity($cell);

        $assignment = (new Assignment())
            ->setInmate($inmate)
            ->setCell($cell)
            ->setReason($reason)
            ->setCreatedBy($actor);

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($assignment, $actor, $cell, $inmate): void {
            $entityManager->persist($assignment);
            $entityManager->flush();

            $entityManager->persist($this->auditLogger->create($actor, 'ASSIGNMENT_CREATED', $assignment, [
                'cell' => $cell->getNumber(),
                'inmate' => $inmate->getUid(),
            ]));
        });

        return $assignment;
    }

    private function assertInmateCanBeAssigned(Inmate $inmate): void
    {
        if (in_array($inmate->getStatus(), [Inmate::STATUS_RELEASED, Inmate::STATUS_EXTERNAL_TRANSFER], true)) {
            throw AssignmentException::inmateCannotBeAssigned($inmate);
        }
    }

    private function assertInmateHasNoActiveAssignment(Inmate $inmate): void
    {
        if ($this->assignmentRepository->findActiveForInmate($inmate) !== null) {
            throw AssignmentException::inmateAlreadyAssigned($inmate);
        }
    }

    private function assertCellHasCapacity(Cell $cell): void
    {
        if ($this->assignmentRepository->countActiveAssignmentsForCell($cell) >= $cell->getCapacity()) {
            throw AssignmentException::cellCapacityExceeded($cell);
        }
    }
}
