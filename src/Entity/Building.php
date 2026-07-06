<?php

namespace App\Entity;

use App\Repository\BuildingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BuildingRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_building_code', fields: ['code'])]
#[UniqueEntity(fields: ['code'], message: 'Ce code batiment est deja utilise.')]
class Building
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    private string $code = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $address = null;

    #[ORM\Column]
    private bool $active = true;

    /**
     * @var Collection<int, Wing>
     */
    #[ORM\OneToMany(mappedBy: 'building', targetEntity: Wing::class, orphanRemoval: true)]
    private Collection $wings;

    /**
     * @var Collection<int, ManagerUser>
     */
    #[ORM\OneToMany(mappedBy: 'managedBuilding', targetEntity: ManagerUser::class)]
    private Collection $managers;

    public function __construct()
    {
        $this->wings = new ArrayCollection();
        $this->managers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = mb_strtoupper(trim($code));

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address !== null ? trim($address) : null;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Wing>
     */
    public function getWings(): Collection
    {
        return $this->wings;
    }

    public function addWing(Wing $wing): self
    {
        if (!$this->wings->contains($wing)) {
            $this->wings->add($wing);
            $wing->setBuilding($this);
        }

        return $this;
    }

    public function removeWing(Wing $wing): self
    {
        if ($this->wings->removeElement($wing) && $wing->getBuilding() === $this) {
            $wing->setBuilding(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ManagerUser>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }
}
