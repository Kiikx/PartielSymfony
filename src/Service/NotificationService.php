<?php

namespace App\Service;

use App\Entity\Incident;
use App\Entity\ManagerUser;
use App\Entity\Notification;
use App\Repository\UserRepository;

final class NotificationService implements IncidentNotificationCreatorInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @return list<Notification>
     */
    public function createHighSeverityIncidentNotifications(Incident $incident): array
    {
        if (!$this->requiresManagerNotification($incident)) {
            return [];
        }

        $building = $incident->getCell()?->getWing()?->getBuilding();
        if ($building === null) {
            return [];
        }

        $notifications = [];
        foreach ($this->userRepository->findActiveManagersForBuilding($building) as $manager) {
            $notifications[] = $this->createIncidentNotification($manager, $incident);
        }

        return $notifications;
    }

    private function requiresManagerNotification(Incident $incident): bool
    {
        return in_array($incident->getSeverity(), [Incident::SEVERITY_HIGH, Incident::SEVERITY_CRITICAL], true);
    }

    private function createIncidentNotification(ManagerUser $manager, Incident $incident): Notification
    {
        return (new Notification())
            ->setRecipient($manager)
            ->setSubject(sprintf('Incident %s: %s', $incident->getSeverity(), $incident->getTitle()))
            ->setChannel(Notification::CHANNEL_EMAIL)
            ->setStatus(Notification::STATUS_PENDING);
    }
}
