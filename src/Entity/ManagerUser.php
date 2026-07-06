<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ManagerUser extends User
{
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $managedBuilding = null;

    public function getManagedBuilding(): ?string
    {
        return $this->managedBuilding;
    }

    public function setManagedBuilding(?string $managedBuilding): self
    {
        $this->managedBuilding = $managedBuilding !== null ? trim($managedBuilding) : null;

        return $this;
    }

    protected function getDefaultRole(): string
    {
        return 'ROLE_MANAGER';
    }
}
