<?php

namespace App\Entity;

use App\Repository\WingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WingRepository::class)]
class Wing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(0)]
    private int $floor = 0;

    #[ORM\ManyToOne(inversedBy: 'wings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Building $building = null;

    /**
     * @var Collection<int, Cell>
     */
    #[ORM\OneToMany(mappedBy: 'wing', targetEntity: Cell::class, orphanRemoval: true)]
    private Collection $cells;

    /**
     * @var Collection<int, GuardUser>
     */
    #[ORM\OneToMany(mappedBy: 'assignedZone', targetEntity: GuardUser::class)]
    private Collection $guards;

    public function __construct()
    {
        $this->cells = new ArrayCollection();
        $this->guards = new ArrayCollection();
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

    public function getFloor(): int
    {
        return $this->floor;
    }

    public function setFloor(int $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    public function getBuilding(): ?Building
    {
        return $this->building;
    }

    public function setBuilding(?Building $building): self
    {
        $this->building = $building;

        return $this;
    }

    /**
     * @return Collection<int, Cell>
     */
    public function getCells(): Collection
    {
        return $this->cells;
    }

    public function addCell(Cell $cell): self
    {
        if (!$this->cells->contains($cell)) {
            $this->cells->add($cell);
            $cell->setWing($this);
        }

        return $this;
    }

    public function removeCell(Cell $cell): self
    {
        if ($this->cells->removeElement($cell) && $cell->getWing() === $this) {
            $cell->setWing(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, GuardUser>
     */
    public function getGuards(): Collection
    {
        return $this->guards;
    }
}
