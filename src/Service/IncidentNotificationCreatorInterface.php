<?php

namespace App\Service;

use App\Entity\Incident;
use App\Entity\Notification;

interface IncidentNotificationCreatorInterface
{
    /**
     * @return list<Notification>
     */
    public function createHighSeverityIncidentNotifications(Incident $incident): array;
}
