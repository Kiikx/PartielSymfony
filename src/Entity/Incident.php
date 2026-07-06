<?php

namespace App\Entity;

use App\Repository\IncidentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IncidentRepository::class)]
#[ORM\Index(name: 'idx_incident_severity', fields: ['severity'])]
#[ORM\Index(name: 'idx_incident_occurred_at', fields: ['occurredAt'])]
#[ORM\Index(name: 'idx_incident_status', fields: ['status'])]
class Incident
{
    public const SEVERITY_LOW = 'FAIBLE';
    public const SEVERITY_MEDIUM = 'MOYENNE';
    public const SEVERITY_HIGH = 'ELEVEE';
    public const SEVERITY_CRITICAL = 'CRITIQUE';

    public const SEVERITIES = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    public const STATUS_DRAFT = 'BROUILLON';
    public const STATUS_OPEN = 'OUVERT';
    public const STATUS_PROCESSING = 'EN_TRAITEMENT';
    public const STATUS_CLOSED = 'CLOS';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_PROCESSING,
        self::STATUS_CLOSED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $description = '';

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::SEVERITIES)]
    private string $severity = self::SEVERITY_LOW;

    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $occurredAt;

    #[ORM\ManyToOne(inversedBy: 'incidents')]
    private ?Cell $cell = null;

    #[ORM\ManyToOne(inversedBy: 'reportedIncidents')]
    private ?User $reportedBy = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_OPEN;

    /**
     * @var Collection<int, Inmate>
     */
    #[ORM\ManyToMany(targetEntity: Inmate::class, inversedBy: 'incidents')]
    #[ORM\JoinTable(name: 'incident_inmate')]
    private Collection $inmates;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
        $this->inmates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);

        return $this;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): self
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function getCell(): ?Cell
    {
        return $this->cell;
    }

    public function setCell(?Cell $cell): self
    {
        $this->cell = $cell;

        return $this;
    }

    public function getReportedBy(): ?User
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?User $reportedBy): self
    {
        $this->reportedBy = $reportedBy;

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

    /**
     * @return Collection<int, Inmate>
     */
    public function getInmates(): Collection
    {
        return $this->inmates;
    }

    public function addInmate(Inmate $inmate): self
    {
        if (!$this->inmates->contains($inmate)) {
            $this->inmates->add($inmate);
        }

        return $this;
    }

    public function removeInmate(Inmate $inmate): self
    {
        $this->inmates->removeElement($inmate);

        return $this;
    }
}
