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

    #[ORM\ManyToOne(inversedBy: 'guards')]
    private ?Wing $assignedZone = null;

    public function getBadgeNumber(): string
    {
        return $this->badgeNumber;
    }

    public function setBadgeNumber(string $badgeNumber): self
    {
        $this->badgeNumber = trim($badgeNumber);

        return $this;
    }

    public function getAssignedZone(): ?Wing
    {
        return $this->assignedZone;
    }

    public function setAssignedZone(?Wing $assignedZone): self
    {
        $this->assignedZone = $assignedZone;

        return $this;
    }

    protected function getDefaultRole(): string
    {
        return 'ROLE_GUARD';
    }
}
