<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class GuardUser extends User
{
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $badgeNumber = '';

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $assignedZone = null;

    public function getBadgeNumber(): string
    {
        return $this->badgeNumber;
    }

    public function setBadgeNumber(string $badgeNumber): self
    {
        $this->badgeNumber = trim($badgeNumber);

        return $this;
    }

    public function getAssignedZone(): ?string
    {
        return $this->assignedZone;
    }

    public function setAssignedZone(?string $assignedZone): self
    {
        $this->assignedZone = $assignedZone !== null ? trim($assignedZone) : null;

        return $this;
    }

    protected function getDefaultRole(): string
    {
        return 'ROLE_GUARD';
    }
}
