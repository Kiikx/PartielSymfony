<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ManagerUser extends User
{
    #[ORM\ManyToOne(inversedBy: 'managers')]
    private ?Building $managedBuilding = null;

    public function getManagedBuilding(): ?Building
    {
        return $this->managedBuilding;
    }

    public function setManagedBuilding(?Building $managedBuilding): self
    {
        $this->managedBuilding = $managedBuilding;

        return $this;
    }

    protected function getDefaultRole(): string
    {
        return 'ROLE_MANAGER';
    }
}
