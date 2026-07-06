<?php

namespace App\Entity;

use App\Repository\CellRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CellRepository::class)]
#[ORM\Index(name: 'idx_cell_status', fields: ['status'])]
#[ORM\UniqueConstraint(name: 'uniq_cell_number_wing', fields: ['number', 'wing'])]
#[UniqueEntity(fields: ['number', 'wing'], message: 'Ce numero de cellule existe deja dans cette aile.')]
class Cell
{
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_FULL = 'FULL';
    public const STATUS_MAINTENANCE = 'MAINTENANCE';
    public const STATUS_CLOSED = 'CLOSED';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_FULL,
        self::STATUS_MAINTENANCE,
        self::STATUS_CLOSED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    private string $number = '';

    #[ORM\Column]
    #[Assert\Positive]
    private int $capacity = 1;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_AVAILABLE;

    #[ORM\ManyToOne(inversedBy: 'cells')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Wing $wing = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = mb_strtoupper(trim($number));

        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getWing(): ?Wing
    {
        return $this->wing;
    }

    public function setWing(?Wing $wing): self
    {
        $this->wing = $wing;

        return $this;
    }
}
