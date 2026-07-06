<?php

namespace App\Repository;

use App\Entity\Building;
use App\Entity\ManagerUser;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return list<ManagerUser>
     */
    public function findActiveManagersForBuilding(Building $building): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('manager')
            ->from(ManagerUser::class, 'manager')
            ->andWhere('manager.managedBuilding = :building')
            ->andWhere('manager.isActive = true')
            ->setParameter('building', $building)
            ->getQuery()
            ->getResult();
    }
}
