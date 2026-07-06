<?php

namespace App\Entity;

use App\Repository\ActivityParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityParticipationRepository::class)]
#[ORM\Index(name: 'idx_activity_participation_status', fields: ['status'])]
#[ORM\UniqueConstraint(name: 'uniq_activity_inmate_participation', fields: ['activity', 'inmate'])]
class ActivityParticipation
{
    public const STATUS_PRESENT = 'PRESENT';
    public const STATUS_ABSENT = 'ABSENT';
    public const STATUS_EXCUSED = 'EXCUSE';
    public const STATUS_REFUSED = 'REFUS';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_EXCUSED,
        self::STATUS_REFUSED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'activityParticipations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Inmate $inmate = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Activity $activity = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_PRESENT;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $checkedAt = null;

    #[ORM\ManyToOne(inversedBy: 'checkedActivityParticipations')]
    private ?User $checkedBy = null;

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

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): self
    {
        $this->activity = $activity;

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

    public function getCheckedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(?\DateTimeImmutable $checkedAt): self
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }

    public function getCheckedBy(): ?User
    {
        return $this->checkedBy;
    }

    public function setCheckedBy(?User $checkedBy): self
    {
        $this->checkedBy = $checkedBy;

        return $this;
    }
}
