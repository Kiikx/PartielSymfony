<?php

namespace App\Tests\Security;

use App\Entity\AdminUser;
use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\Incident;
use App\Entity\ManagerUser;
use App\Entity\User;
use App\Entity\Wing;
use App\Security\Voter\IncidentVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class IncidentVoterTest extends TestCase
{
    private IncidentVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new IncidentVoter();
    }

    #[DataProvider('adminAttributes')]
    public function testAdminCanManageEveryIncident(string $attribute): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($this->createAdmin()), $this->createIncident(), [$attribute])
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminAttributes(): iterable
    {
        yield 'view' => [IncidentVoter::VIEW];
        yield 'edit' => [IncidentVoter::EDIT];
        yield 'close' => [IncidentVoter::CLOSE];
    }

    public function testAdminCanCreateIncident(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($this->createAdmin()), null, [IncidentVoter::CREATE])
        );
    }

    #[DataProvider('incidentCreators')]
    public function testOperationalUsersCanCreateIncident(User $user): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($user), null, [IncidentVoter::CREATE])
        );
    }

    /**
     * @return iterable<string, array{User}>
     */
    public static function incidentCreators(): iterable
    {
        $building = (new Building())
            ->setName('Building A')
            ->setCode('A');
        $wing = (new Wing())
            ->setName('Wing A')
            ->setFloor(1)
            ->setBuilding($building);

        $manager = new ManagerUser();
        $manager
            ->setEmail('manager@pas.test')
            ->setFirstName('Kiki')
            ->setLastName('White');
        $manager->setManagedBuilding($building);

        $guard = new GuardUser();
        $guard
            ->setEmail('guard@pas.test')
            ->setFirstName('Ness')
            ->setLastName('Cake');
        $guard
            ->setBadgeNumber('PAS-G-001')
            ->setAssignedZone($wing);

        yield 'manager' => [
            $manager,
        ];

        yield 'guard' => [
            $guard,
        ];
    }

    public function testManagerCanAccessIncidentInManagedBuilding(): void
    {
        $building = $this->createBuilding('A');
        $manager = $this->createManager($building);
        $incident = $this->createIncident($building);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($manager), $incident, [IncidentVoter::VIEW])
        );
    }

    public function testManagerCannotAccessIncidentFromAnotherBuilding(): void
    {
        $manager = $this->createManager($this->createBuilding('A'));
        $incident = $this->createIncident($this->createBuilding('B'));

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createToken($manager), $incident, [IncidentVoter::VIEW])
        );
    }

    public function testGuardCanEditOwnOpenIncident(): void
    {
        $guard = $this->createGuard();
        $incident = $this->createIncident(status: Incident::STATUS_OPEN, reportedBy: $guard);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($guard), $incident, [IncidentVoter::EDIT])
        );
    }

    public function testGuardCannotEditClosedIncident(): void
    {
        $guard = $this->createGuard();
        $incident = $this->createIncident(status: Incident::STATUS_CLOSED, reportedBy: $guard);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createToken($guard), $incident, [IncidentVoter::EDIT])
        );
    }

    public function testInactiveUserCannotCreateIncident(): void
    {
        $guard = $this->createGuard();
        $guard->setIsActive(false);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createToken($guard), null, [IncidentVoter::CREATE])
        );
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function createAdmin(): AdminUser
    {
        $admin = new AdminUser();
        $admin
            ->setEmail('admin@pas.test')
            ->setFirstName('David')
            ->setLastName('Mgr');

        return $admin;
    }

    private function createManager(Building $building): ManagerUser
    {
        $manager = new ManagerUser();
        $manager
            ->setEmail('manager@pas.test')
            ->setFirstName('Kiki')
            ->setLastName('White');
        $manager->setManagedBuilding($building);

        return $manager;
    }

    private function createGuard(?Wing $assignedZone = null): GuardUser
    {
        $guard = new GuardUser();
        $guard
            ->setEmail('guard@pas.test')
            ->setFirstName('Ness')
            ->setLastName('Cake');
        $guard
            ->setBadgeNumber('PAS-G-001')
            ->setAssignedZone($assignedZone);

        return $guard;
    }

    private function createIncident(
        ?Building $building = null,
        string $status = Incident::STATUS_OPEN,
        ?User $reportedBy = null,
    ): Incident {
        return (new Incident())
            ->setTitle('Incident test')
            ->setDescription('Description test')
            ->setStatus($status)
            ->setCell($this->createCell($building ?? $this->createBuilding('A')))
            ->setReportedBy($reportedBy);
    }

    private function createBuilding(string $code): Building
    {
        return (new Building())
            ->setName('Building '.$code)
            ->setCode($code);
    }

    private function createCell(Building $building): Cell
    {
        $wing = (new Wing())
            ->setName('Wing '.$building->getCode())
            ->setFloor(1)
            ->setBuilding($building);

        return (new Cell())
            ->setNumber('101')
            ->setCapacity(2)
            ->setWing($wing);
    }
}
