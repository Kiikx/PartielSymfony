<?php

namespace App\Entity;

use App\Repository\TransferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransferRepository::class)]
#[ORM\Index(name: 'idx_transfer_scheduled_at', fields: ['scheduledAt'])]
#[ORM\Index(name: 'idx_transfer_type', fields: ['type'])]
class Transfer
{
    public const TYPE_INTERNAL = 'INTERNE';
    public const TYPE_EXTERNAL = 'EXTERNE';

    public const TYPES = [
        self::TYPE_INTERNAL,
        self::TYPE_EXTERNAL,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transfers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Inmate $inmate = null;

    #[ORM\ManyToOne(inversedBy: 'outgoingTransfers')]
    private ?Cell $fromCell = null;

    #[ORM\ManyToOne(inversedBy: 'incomingTransfers')]
    private ?Cell $toCell = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $externalDestination = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::TYPES)]
    private string $type = self::TYPE_INTERNAL;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $reason = '';

    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\ManyToOne]
    private ?User $validatedBy = null;

    public function __construct()
    {
        $this->scheduledAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInmate(): ?Inmate
    {
        return $this->inmate;
    }

    public function setInmate(?Inmate $inmate): self
    {
        $this->inmate = $inmate;

        return $this;
    }

    public function getFromCell(): ?Cell
    {
        return $this->fromCell;
    }

    public function setFromCell(?Cell $fromCell): self
    {
        $this->fromCell = $fromCell;

        return $this;
    }

    public function getToCell(): ?Cell
    {
        return $this->toCell;
    }

    public function setToCell(?Cell $toCell): self
    {
        $this->toCell = $toCell;

        return $this;
    }

    public function getExternalDestination(): ?string
    {
        return $this->externalDestination;
    }

    public function setExternalDestination(?string $externalDestination): self
    {
        $this->externalDestination = $externalDestination !== null ? trim($externalDestination) : null;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = trim($reason);

        return $this;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): self
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }
}
