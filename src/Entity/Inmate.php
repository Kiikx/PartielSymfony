<?php

namespace App\Entity;

use App\Repository\InmateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InmateRepository::class)]
#[ORM\Index(name: 'idx_inmate_status', fields: ['status'])]
#[ORM\Index(name: 'idx_inmate_security_level', fields: ['securityLevel'])]
#[ORM\UniqueConstraint(name: 'uniq_inmate_uid', fields: ['uid'])]
#[UniqueEntity(fields: ['uid'], message: 'Ce numero d ecrou est deja utilise.')]
class Inmate
{
    public const STATUS_INCARCERATED = 'INCARCERE';
    public const STATUS_RELEASED = 'SORTI';
    public const STATUS_EXTERNAL_TRANSFER = 'TRANSFERE_EXTERNE';
    public const STATUS_MEDICAL_LEAVE = 'PERMISSION_MEDICALE';

    public const STATUSES = [
        self::STATUS_INCARCERATED,
        self::STATUS_RELEASED,
        self::STATUS_EXTERNAL_TRANSFER,
        self::STATUS_MEDICAL_LEAVE,
    ];

    public const SECURITY_LOW = 'LOW';
    public const SECURITY_MEDIUM = 'MEDIUM';
    public const SECURITY_HIGH = 'HIGH';
    public const SECURITY_CRITICAL = 'CRITICAL';

    public const SECURITY_LEVELS = [
        self::SECURITY_LOW,
        self::SECURITY_MEDIUM,
        self::SECURITY_HIGH,
        self::SECURITY_CRITICAL,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $uid = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $lastName = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual('today')]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_INCARCERATED;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::SECURITY_LEVELS)]
    private string $securityLevel = self::SECURITY_MEDIUM;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $arrivalDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $releaseDate = null;

    /**
     * @var Collection<int, Assignment>
     */
    #[ORM\OneToMany(mappedBy: 'inmate', targetEntity: Assignment::class, orphanRemoval: true)]
    #[ORM\OrderBy(['startAt' => 'DESC'])]
    private Collection $assignments;

    /**
     * @var Collection<int, Transfer>
     */
    #[ORM\OneToMany(mappedBy: 'inmate', targetEntity: Transfer::class, orphanRemoval: true)]
    #[ORM\OrderBy(['scheduledAt' => 'DESC'])]
    private Collection $transfers;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
        $this->transfers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): self
    {
        $this->uid = mb_strtoupper(trim($uid));

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = trim($firstName);

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = trim($lastName);

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;

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

    public function getSecurityLevel(): string
    {
        return $this->securityLevel;
    }

    public function setSecurityLevel(string $securityLevel): self
    {
        $this->securityLevel = $securityLevel;

        return $this;
    }

    public function getArrivalDate(): ?\DateTimeImmutable
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(?\DateTimeImmutable $arrivalDate): self
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeImmutable $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setInmate($this);
        }

        return $this;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->removeElement($assignment) && $assignment->getInmate() === $this) {
            $assignment->setInmate(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Transfer>
     */
    public function getTransfers(): Collection
    {
        return $this->transfers;
    }

    public function addTransfer(Transfer $transfer): self
    {
        if (!$this->transfers->contains($transfer)) {
            $this->transfers->add($transfer);
            $transfer->setInmate($this);
        }

        return $this;
    }

    public function removeTransfer(Transfer $transfer): self
    {
        if ($this->transfers->removeElement($transfer) && $transfer->getInmate() === $this) {
            $transfer->setInmate(null);
        }

        return $this;
    }
}
