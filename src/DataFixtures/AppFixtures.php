<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\ActivityParticipation;
use App\Entity\AdminUser;
use App\Entity\Assignment;
use App\Entity\AuditLog;
use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\Incident;
use App\Entity\Inmate;
use App\Entity\ManagerUser;
use App\Entity\Transfer;
use App\Entity\User;
use App\Entity\Wing;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEMO_PASSWORD = 'Password123!';

    private const INMATE_FIRST_NAMES = [
        'Adam',
        'Bilal',
        'Cedric',
        'Dorian',
        'Elias',
        'Farid',
        'Gabriel',
        'Hakim',
        'Ilyes',
        'Jonas',
    ];

    private const INMATE_LAST_NAMES = [
        'Martin',
        'Bernard',
        'Petit',
        'Robert',
        'Richard',
        'Durand',
        'Moreau',
        'Laurent',
        'Simon',
        'Michel',
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $buildings = $this->createBuildings();
        $wings = $this->createWings($buildings);
        $cells = $this->createCells($wings);
        $users = $this->createUsers($buildings, $wings);
        $inmates = $this->createInmates();
        $assignments = $this->createAssignments($inmates, $cells, $users['manager']);
        $transfers = $this->createTransfers($inmates, $cells, $users['manager']);
        $activities = $this->createActivities($users['guard']);
        $participations = $this->createParticipations($activities, $inmates, $users['guard']);
        $incidents = $this->createIncidents($inmates, $cells, $users['guard']);
        $auditLogs = $this->createAuditLogs($users, $incidents);

        foreach ([
            ...$buildings,
            ...$wings,
            ...$cells,
            ...array_values($users),
            ...$inmates,
            ...$assignments,
            ...$transfers,
            ...$activities,
            ...$participations,
            ...$incidents,
            ...$auditLogs,
        ] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return list<Building>
     */
    private function createBuildings(): array
    {
        return [
            (new Building())
                ->setName('Maison centrale Paris Sud')
                ->setCode('MC-PS')
                ->setAddress('12 avenue de la Securite, 75014 Paris')
                ->setActive(true),
            (new Building())
                ->setName('Centre de detention Seine Est')
                ->setCode('CD-SE')
                ->setAddress('8 rue des Remparts, 94000 Creteil')
                ->setActive(true),
        ];
    }

    /**
     * @param list<Building> $buildings
     *
     * @return list<Wing>
     */
    private function createWings(array $buildings): array
    {
        return [
            $this->createWing('Aile Nord', 1, $buildings[0]),
            $this->createWing('Aile Sud', 2, $buildings[0]),
            $this->createWing('Aile Est', 1, $buildings[1]),
            $this->createWing('Aile Ouest', 2, $buildings[1]),
        ];
    }

    private function createWing(string $name, int $floor, Building $building): Wing
    {
        return (new Wing())
            ->setName($name)
            ->setFloor($floor)
            ->setBuilding($building);
    }

    /**
     * @param list<Wing> $wings
     *
     * @return list<Cell>
     */
    private function createCells(array $wings): array
    {
        $cells = [];
        foreach ($wings as $wingIndex => $wing) {
            for ($number = 1; $number <= 5; ++$number) {
                $cellIndex = ($wingIndex * 5) + $number;
                $cells[] = $this->createCell(
                    sprintf('%s-%03d', chr(65 + $wingIndex), $number),
                    $cellIndex % 5 === 0 ? 3 : 2,
                    $cellIndex % 9 === 0 ? Cell::STATUS_MAINTENANCE : Cell::STATUS_AVAILABLE,
                    $wing,
                );
            }
        }

        return $cells;
    }

    private function createCell(string $number, int $capacity, string $status, Wing $wing): Cell
    {
        return (new Cell())
            ->setNumber($number)
            ->setCapacity($capacity)
            ->setStatus($status)
            ->setWing($wing);
    }

    /**
     * @param list<Building> $buildings
     * @param list<Wing>     $wings
     *
     * @return array{admin: AdminUser, manager: ManagerUser, secondManager: ManagerUser, guard: GuardUser, secondGuard: GuardUser}
     */
    private function createUsers(array $buildings, array $wings): array
    {
        $admin = new AdminUser();
        $admin
            ->setEmail('admin@pas.test')
            ->setFirstName('David')
            ->setLastName('Mgr');
        $admin
            ->setService('Direction')
            ->setSuperAdmin(true);

        $manager = new ManagerUser();
        $manager
            ->setEmail('manager@pas.test')
            ->setFirstName('Kiki')
            ->setLastName('White');
        $manager->setManagedBuilding($buildings[0]);

        $secondManager = new ManagerUser();
        $secondManager
            ->setEmail('manager.seine@pas.test')
            ->setFirstName('Ness')
            ->setLastName('Cake');
        $secondManager->setManagedBuilding($buildings[1]);

        $guard = new GuardUser();
        $guard
            ->setEmail('guard@pas.test')
            ->setFirstName('Ness')
            ->setLastName('Cake');
        $guard
            ->setBadgeNumber('PAS-G-001')
            ->setAssignedZone($wings[0]);

        $secondGuard = new GuardUser();
        $secondGuard
            ->setEmail('guard.paris@pas.test')
            ->setFirstName('David')
            ->setLastName('Mgr');
        $secondGuard
            ->setBadgeNumber('PAS-G-002')
            ->setAssignedZone($wings[2]);

        foreach ([$admin, $manager, $secondManager, $guard, $secondGuard] as $user) {
            $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));
        }

        return [
            'admin' => $admin,
            'manager' => $manager,
            'secondManager' => $secondManager,
            'guard' => $guard,
            'secondGuard' => $secondGuard,
        ];
    }

    /**
     * @return list<Inmate>
     */
    private function createInmates(): array
    {
        $inmates = [];
        for ($index = 1; $index <= 50; ++$index) {
            $status = match (true) {
                $index > 44 => Inmate::STATUS_EXTERNAL_TRANSFER,
                $index > 38 => Inmate::STATUS_RELEASED,
                $index % 17 === 0 => Inmate::STATUS_MEDICAL_LEAVE,
                default => Inmate::STATUS_INCARCERATED,
            };

            $inmate = (new Inmate())
                ->setUid(sprintf('PAS-%05d', $index))
                ->setFirstName(self::INMATE_FIRST_NAMES[($index - 1) % count(self::INMATE_FIRST_NAMES)])
                ->setLastName(self::INMATE_LAST_NAMES[($index - 1) % count(self::INMATE_LAST_NAMES)])
                ->setBirthDate(new \DateTimeImmutable(sprintf('-%d years', 24 + ($index % 31))))
                ->setArrivalDate(new \DateTimeImmutable(sprintf('-%d days', 30 + ($index * 9))))
                ->setStatus($status)
                ->setSecurityLevel($this->resolveSecurityLevel($index));

            if ($status === Inmate::STATUS_RELEASED) {
                $inmate->setReleaseDate(new \DateTimeImmutable(sprintf('-%d days', 4 + $index)));
            }

            $inmates[] = $inmate;
        }

        return $inmates;
    }

    private function resolveSecurityLevel(int $index): string
    {
        return match (true) {
            $index % 19 === 0 => Inmate::SECURITY_CRITICAL,
            $index % 7 === 0 => Inmate::SECURITY_HIGH,
            $index % 3 === 0 => Inmate::SECURITY_LOW,
            default => Inmate::SECURITY_MEDIUM,
        };
    }

    /**
     * @param list<Inmate> $inmates
     * @param list<Cell>   $cells
     *
     * @return list<Assignment>
     */
    private function createAssignments(array $inmates, array $cells, User $createdBy): array
    {
        $assignments = [];
        $assignableCells = array_values(array_filter(
            $cells,
            static fn (Cell $cell): bool => $cell->getStatus() !== Cell::STATUS_MAINTENANCE,
        ));

        foreach ($inmates as $index => $inmate) {
            $cell = $assignableCells[$index % count($assignableCells)];
            $assignment = (new Assignment())
                ->setInmate($inmate)
                ->setCell($cell)
                ->setStartAt(new \DateTimeImmutable(sprintf('-%d days', 20 + ($index * 6))))
                ->setReason('Affectation de demonstration')
                ->setCreatedBy($createdBy);

            if ($index >= 36) {
                $assignment->setEndAt(new \DateTimeImmutable(sprintf('-%d days', 3 + ($index % 10))));
            }

            $assignments[] = $assignment;
        }

        return $assignments;
    }

    /**
     * @param list<Inmate> $inmates
     * @param list<Cell>   $cells
     *
     * @return list<Transfer>
     */
    private function createTransfers(array $inmates, array $cells, User $validatedBy): array
    {
        $transfers = [];
        for ($index = 0; $index < 8; ++$index) {
            $isExternal = $index >= 5;
            $transfers[] = (new Transfer())
                ->setInmate($inmates[42 + $index])
                ->setFromCell($cells[$index])
                ->setToCell($isExternal ? null : $cells[$index + 8])
                ->setExternalDestination($isExternal ? 'Etablissement partenaire '.$index : null)
                ->setType($isExternal ? Transfer::TYPE_EXTERNAL : Transfer::TYPE_INTERNAL)
                ->setReason($isExternal ? 'Transfert administratif externe' : 'Reequilibrage capacite')
                ->setScheduledAt(new \DateTimeImmutable(sprintf('-%d days', 2 + $index)))
                ->setValidatedBy($validatedBy);
        }

        return $transfers;
    }

    /**
     * @return list<Activity>
     */
    private function createActivities(User $createdBy): array
    {
        return [
            $this->createActivity(Activity::TYPE_PRESENCE_CHECK, 'Pointage matin', 'Aile Nord', '-2 hours', $createdBy),
            $this->createActivity(Activity::TYPE_WALK, 'Promenade surveillee', 'Cour A', '+1 hour', $createdBy),
            $this->createActivity(Activity::TYPE_CANTEEN, 'Service cantine midi', 'Refectoire', '+3 hours', $createdBy),
            $this->createActivity(Activity::TYPE_WORKSHOP, 'Atelier menuiserie', 'Atelier 2', '+1 day', $createdBy),
            $this->createActivity(Activity::TYPE_APPOINTMENT, 'Rendez-vous medical', 'Infirmerie', '+2 days', $createdBy),
            $this->createActivity(Activity::TYPE_PRESENCE_CHECK, 'Pointage soir', 'Aile Est', '+8 hours', $createdBy),
        ];
    }

    private function createActivity(string $type, string $label, string $location, string $schedule, User $createdBy): Activity
    {
        return (new Activity())
            ->setType($type)
            ->setLabel($label)
            ->setLocation($location)
            ->setScheduledAt(new \DateTimeImmutable($schedule))
            ->setCreatedBy($createdBy);
    }

    /**
     * @param list<Activity> $activities
     * @param list<Inmate>   $inmates
     *
     * @return list<ActivityParticipation>
     */
    private function createParticipations(array $activities, array $inmates, User $checkedBy): array
    {
        $participations = [];
        foreach ($activities as $activityIndex => $activity) {
            for ($inmateIndex = 0; $inmateIndex < 12; ++$inmateIndex) {
                $participations[] = (new ActivityParticipation())
                    ->setActivity($activity)
                    ->setInmate($inmates[($activityIndex * 6 + $inmateIndex) % 36])
                    ->setStatus($this->resolveParticipationStatus($activityIndex + $inmateIndex))
                    ->setCheckedAt(new \DateTimeImmutable(sprintf('-%d minutes', 15 + $inmateIndex)))
                    ->setCheckedBy($checkedBy);
            }
        }

        return $participations;
    }

    private function resolveParticipationStatus(int $index): string
    {
        return match (true) {
            $index % 11 === 0 => ActivityParticipation::STATUS_REFUSED,
            $index % 7 === 0 => ActivityParticipation::STATUS_EXCUSED,
            $index % 5 === 0 => ActivityParticipation::STATUS_ABSENT,
            default => ActivityParticipation::STATUS_PRESENT,
        };
    }

    /**
     * @param list<Inmate> $inmates
     * @param list<Cell>   $cells
     *
     * @return list<Incident>
     */
    private function createIncidents(array $inmates, array $cells, User $reportedBy): array
    {
        $incidents = [
            $this->createIncident('Altercation en coursive', 'Deux detenus impliques, intervention rapide.', Incident::SEVERITY_HIGH, Incident::STATUS_OPEN, '-1 day', $cells[1], $reportedBy, [$inmates[2], $inmates[7]]),
            $this->createIncident('Objet interdit trouve', 'Controle cellule avec saisie de telephone.', Incident::SEVERITY_MEDIUM, Incident::STATUS_PROCESSING, '-3 days', $cells[4], $reportedBy, [$inmates[10]]),
            $this->createIncident('Refus de reintegration', 'Retour promenade refuse pendant quinze minutes.', Incident::SEVERITY_LOW, Incident::STATUS_CLOSED, '-5 days', $cells[8], $reportedBy, [$inmates[12]]),
            $this->createIncident('Alerte medicale', 'Malaise signale pendant le pointage.', Incident::SEVERITY_CRITICAL, Incident::STATUS_OPEN, '-6 hours', $cells[11], $reportedBy, [$inmates[16]]),
            $this->createIncident('Degradation materielle', 'Luminaire endommage en cellule.', Incident::SEVERITY_MEDIUM, Incident::STATUS_DRAFT, '-2 hours', $cells[14], $reportedBy, [$inmates[20]]),
        ];

        return $incidents;
    }

    /**
     * @param list<Inmate> $inmates
     */
    private function createIncident(
        string $title,
        string $description,
        string $severity,
        string $status,
        string $occurredAt,
        Cell $cell,
        User $reportedBy,
        array $inmates,
    ): Incident {
        $incident = (new Incident())
            ->setTitle($title)
            ->setDescription($description)
            ->setSeverity($severity)
            ->setStatus($status)
            ->setOccurredAt(new \DateTimeImmutable($occurredAt))
            ->setCell($cell)
            ->setReportedBy($reportedBy);

        foreach ($inmates as $inmate) {
            $incident->addInmate($inmate);
        }

        return $incident;
    }

    /**
     * @param array{admin: AdminUser, manager: ManagerUser, secondManager: ManagerUser, guard: GuardUser, secondGuard: GuardUser} $users
     * @param list<Incident> $incidents
     *
     * @return list<AuditLog>
     */
    private function createAuditLogs(array $users, array $incidents): array
    {
        return [
            $this->createAuditLog($users['admin'], 'USER_CREATED', AdminUser::class, ['email' => 'admin@pas.test'], '-12 days'),
            $this->createAuditLog($users['manager'], 'ASSIGNMENT_CREATED', Assignment::class, ['count' => 50], '-9 days'),
            $this->createAuditLog($users['guard'], 'INCIDENT_CREATED', Incident::class, ['title' => $incidents[0]->getTitle()], '-1 day'),
            $this->createAuditLog($users['manager'], 'INCIDENT_STATUS_UPDATED', Incident::class, ['fromStatus' => Incident::STATUS_OPEN, 'toStatus' => Incident::STATUS_PROCESSING], '-3 hours'),
            $this->createAuditLog($users['secondGuard'], 'ACTIVITY_CHECKED', ActivityParticipation::class, ['status' => ActivityParticipation::STATUS_PRESENT], '-45 minutes'),
        ];
    }

    /**
     * @param array<string, mixed> $details
     */
    private function createAuditLog(User $actor, string $action, string $entityClass, array $details, string $createdAt): AuditLog
    {
        return (new AuditLog())
            ->setActor($actor)
            ->setAction($action)
            ->setEntityClass($entityClass)
            ->setDetails($details)
            ->setCreatedAt(new \DateTimeImmutable($createdAt))
            ->setIpAddress('127.0.0.1');
    }
}
