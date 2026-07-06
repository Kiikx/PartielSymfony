<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', fields: ['email'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'profile_type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap([
    'admin' => AdminUser::class,
    'manager' => ManagerUser::class,
    'guard' => GuardUser::class,
])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est deja utilisee.')]
abstract class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $email = '';

    /**
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $lastName = '';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     *  Collection<int, Activity>
     */
    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Activity::class)]
    private Collection $createdActivities;

    /**
     *  Collection<int, ActivityParticipation>
     */
    #[ORM\OneToMany(mappedBy: 'checkedBy', targetEntity: ActivityParticipation::class)]
    private Collection $checkedActivityParticipations;

    /**
     *  Collection<int, Incident>
     */
    #[ORM\OneToMany(mappedBy: 'reportedBy', targetEntity: Incident::class)]
    private Collection $reportedIncidents;

    /**
     *  Collection<int, AuditLog>
     */
    #[ORM\OneToMany(mappedBy: 'actor', targetEntity: AuditLog::class)]
    private Collection $auditLogs;

    /**
     *  Collection<int, Notification>
     */
    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Notification::class)]
    private Collection $notifications;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->createdActivities = new ArrayCollection();
        $this->checkedActivityParticipations = new ArrayCollection();
        $this->reportedIncidents = new ArrayCollection();
        $this->auditLogs = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        $roles[] = $this->getDefaultRole();

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     *  Collection<int, Activity>
     */
    public function getCreatedActivities(): Collection
    {
        return $this->createdActivities;
    }

    /**
     *  Collection<int, ActivityParticipation>
     */
    public function getCheckedActivityParticipations(): Collection
    {
        return $this->checkedActivityParticipations;
    }

    /**
     *  Collection<int, Incident>
     */
    public function getReportedIncidents(): Collection
    {
        return $this->reportedIncidents;
    }

    /**
     *  Collection<int, AuditLog>
     */
    public function getAuditLogs(): Collection
    {
        return $this->auditLogs;
    }

    /**
     *  Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    abstract protected function getDefaultRole(): string;
}
