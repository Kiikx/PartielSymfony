<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Index(name: 'idx_activity_type', fields: ['type'])]
#[ORM\Index(name: 'idx_activity_scheduled_at', fields: ['scheduledAt'])]
class Activity
{
    public const TYPE_CANTEEN = 'CANTINE';
    public const TYPE_WALK = 'PROMENADE';
    public const TYPE_WORKSHOP = 'ATELIER';
    public const TYPE_APPOINTMENT = 'RENDEZ_VOUS';
    public const TYPE_PRESENCE_CHECK = 'CONTROLE_PRESENCE';

    public const TYPES = [
        self::TYPE_CANTEEN,
        self::TYPE_WALK,
        self::TYPE_WORKSHOP,
        self::TYPE_APPOINTMENT,
        self::TYPE_PRESENCE_CHECK,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    #[Assert\Choice(choices: self::TYPES)]
    private string $type = self::TYPE_PRESENCE_CHECK;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $label = '';

    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'createdActivities')]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, ActivityParticipation>
     */
    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityParticipation::class, orphanRemoval: true)]
    private Collection $participations;

    public function __construct()
    {
        $this->scheduledAt = new \DateTimeImmutable();
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = trim($label);

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location !== null ? trim($location) : null;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, ActivityParticipation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }
}
