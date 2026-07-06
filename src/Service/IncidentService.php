<?php

namespace App\Service;

use App\Entity\Cell;
use App\Entity\Incident;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class IncidentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
        private readonly IncidentNotificationCreatorInterface $notificationCreator,
    ) {
    }

    public function create(
        string $title,
        string $description,
        string $severity,
        User $reportedBy,
        ?Cell $cell = null,
        ?\DateTimeImmutable $occurredAt = null,
    ): Incident {
        $incident = (new Incident())
            ->setTitle($title)
            ->setDescription($description)
            ->setSeverity($severity)
            ->setReportedBy($reportedBy)
            ->setCell($cell)
            ->setOccurredAt($occurredAt ?? new \DateTimeImmutable())
            ->setStatus(Incident::STATUS_OPEN);

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($incident, $reportedBy): void {
            $entityManager->persist($incident);
            $entityManager->flush();

            foreach ($this->notificationCreator->createHighSeverityIncidentNotifications($incident) as $notification) {
                $entityManager->persist($notification);
            }

            $entityManager->persist($this->auditLogger->create($reportedBy, 'INCIDENT_CREATED', $incident, [
                'severity' => $incident->getSeverity(),
                'status' => $incident->getStatus(),
            ]));
        });

        return $incident;
    }

    public function close(Incident $incident, User $actor): Incident
    {
        $incident->setStatus(Incident::STATUS_CLOSED);

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($actor, $incident): void {
            $entityManager->persist($incident);
            $entityManager->flush();

            $entityManager->persist($this->auditLogger->create($actor, 'INCIDENT_CLOSED', $incident, [
                'severity' => $incident->getSeverity(),
            ]));
        });

        return $incident;
    }
}
