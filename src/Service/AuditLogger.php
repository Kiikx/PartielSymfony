<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;

final class AuditLogger
{
    /**
     * @param array<string, mixed> $details
     */
    public function create(User $actor, string $action, object $entity, array $details = []): AuditLog
    {
        return (new AuditLog())
            ->setActor($actor)
            ->setAction($action)
            ->setEntityClass($entity::class)
            ->setEntityId($this->resolveEntityId($entity))
            ->setDetails($details);
    }

    private function resolveEntityId(object $entity): ?int
    {
        if (!method_exists($entity, 'getId')) {
            return null;
        }

        $id = $entity->getId();

        return is_int($id) ? $id : null;
    }
}
