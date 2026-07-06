<?php

namespace App\DataFixtures;

use App\Entity\AdminUser;
use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\GuardUser;
use App\Entity\ManagerUser;
use App\Entity\User;
use App\Entity\Wing;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEMO_PASSWORD = 'Password123!';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $building = $this->createBuilding();
        $wing = $this->createWing($building);

        $manager->persist($building);
        $manager->persist($wing);

        foreach ($this->createCells($wing) as $cell) {
            $manager->persist($cell);
        }

        foreach ($this->createUsers($building, $wing) as $user) {
            $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));
            $manager->persist($user);
        }

        $manager->flush();
    }

    private function createBuilding(): Building
    {
        return (new Building())
            ->setName('Maison centrale Paris Sud')
            ->setCode('MC-PS')
            ->setAddress('12 avenue de la Securite, 75014 Paris')
            ->setActive(true);
    }

    private function createWing(Building $building): Wing
    {
        return (new Wing())
            ->setName('Aile Nord')
            ->setFloor(1)
            ->setBuilding($building);
    }

    /**
     * @return list<Cell>
     */
    private function createCells(Wing $wing): array
    {
        return [
            $this->createCell('N-101', 2, Cell::STATUS_AVAILABLE, $wing),
            $this->createCell('N-102', 1, Cell::STATUS_AVAILABLE, $wing),
            $this->createCell('N-103', 2, Cell::STATUS_MAINTENANCE, $wing),
        ];
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
     * @return list<User>
     */
    private function createUsers(Building $building, Wing $wing): array
    {
        $admin = (new AdminUser())
            ->setEmail('admin@pas.test')
            ->setFirstName('Alice')
            ->setLastName('Martin')
            ->setService('Direction')
            ->setSuperAdmin(true);

        $manager = (new ManagerUser())
            ->setEmail('manager@pas.test')
            ->setFirstName('Mehdi')
            ->setLastName('Benali')
            ->setManagedBuilding($building);

        $guard = (new GuardUser())
            ->setEmail('guard@pas.test')
            ->setFirstName('Claire')
            ->setLastName('Dubois')
            ->setBadgeNumber('PAS-G-001')
            ->setAssignedZone($wing);

        return [$admin, $manager, $guard];
    }
}
