<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\ActivityParticipation;
use App\Entity\Inmate;
use App\Entity\User;
use App\Repository\ActivityParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ActivityParticipationService
{
    public function __construct(
        private readonly ActivityParticipationRepository $participationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function recordParticipation(Activity $activity, Inmate $inmate, string $status, User $actor): ActivityParticipation
    {
        $participation = $this->participationRepository->findOneBy(['activity' => $activity, 'inmate' => $inmate]);
        $isNew = $participation === null;

        if ($participation === null) {
            $participation = (new ActivityParticipation())
                ->setActivity($activity)
                ->setInmate($inmate);
        }

        $participation
            ->setStatus($status)
            ->setCheckedAt(new \DateTimeImmutable())
            ->setCheckedBy($actor);

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($participation, $actor, $activity, $inmate, $isNew, $status): void {
            $entityManager->persist($participation);
            $entityManager->flush();

            $entityManager->persist($this->auditLogger->create(
                $actor,
                $isNew ? 'ACTIVITY_PARTICIPATION_RECORDED' : 'ACTIVITY_PARTICIPATION_UPDATED',
                $participation,
                [
                    'activity' => $activity->getLabel(),
                    'inmate' => $inmate->getUid(),
                    'status' => $status,
                ],
            ));
        });

        return $participation;
    }
}
