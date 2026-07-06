<?php

namespace App\Security\Voter;

use App\Entity\AdminUser;
use App\Entity\Building;
use App\Entity\GuardUser;
use App\Entity\Incident;
use App\Entity\ManagerUser;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class IncidentVoter extends Voter
{
    public const VIEW = 'incident.view';
    public const CREATE = 'incident.create';
    public const EDIT = 'incident.edit';
    public const CLOSE = 'incident.close';

    private const SUPPORTED_ATTRIBUTES = [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::CLOSE,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, self::SUPPORTED_ATTRIBUTES, true)) {
            return false;
        }

        return $attribute === self::CREATE || $subject instanceof Incident;
    }

    /**
     * @param Incident|null $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        if ($user instanceof AdminUser) {
            return true;
        }

        if ($attribute === self::CREATE) {
            return $user instanceof ManagerUser || $user instanceof GuardUser;
        }

        if (!$subject instanceof Incident) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::CLOSE => $this->canClose($subject, $user),
            default => false,
        };
    }

    private function canView(Incident $incident, User $user): bool
    {
        if ($user instanceof ManagerUser) {
            return $this->isIncidentInManagedBuilding($incident, $user);
        }

        if ($user instanceof GuardUser) {
            return $incident->getReportedBy() === $user || $this->isIncidentInAssignedZone($incident, $user);
        }

        return false;
    }

    private function canEdit(Incident $incident, User $user): bool
    {
        if ($user instanceof ManagerUser) {
            return $this->isIncidentInManagedBuilding($incident, $user) && !$this->isClosed($incident);
        }

        if ($user instanceof GuardUser) {
            return $incident->getReportedBy() === $user && $this->isEditableByGuard($incident);
        }

        return false;
    }

    private function canClose(Incident $incident, User $user): bool
    {
        return $user instanceof ManagerUser
            && $this->isIncidentInManagedBuilding($incident, $user)
            && !$this->isClosed($incident);
    }

    private function isIncidentInManagedBuilding(Incident $incident, ManagerUser $manager): bool
    {
        $managedBuilding = $manager->getManagedBuilding();

        return $managedBuilding instanceof Building && $this->getIncidentBuilding($incident) === $managedBuilding;
    }

    private function isIncidentInAssignedZone(Incident $incident, GuardUser $guard): bool
    {
        $assignedZone = $guard->getAssignedZone();
        $incidentWing = $incident->getCell()?->getWing();

        return $assignedZone !== null && $incidentWing === $assignedZone;
    }

    private function getIncidentBuilding(Incident $incident): ?Building
    {
        return $incident->getCell()?->getWing()?->getBuilding();
    }

    private function isEditableByGuard(Incident $incident): bool
    {
        return in_array($incident->getStatus(), [Incident::STATUS_DRAFT, Incident::STATUS_OPEN], true);
    }

    private function isClosed(Incident $incident): bool
    {
        return $incident->getStatus() === Incident::STATUS_CLOSED;
    }
}
