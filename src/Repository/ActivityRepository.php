<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * @return list<Activity>
     */
    public function findScheduledForDay(\DateTimeImmutable $day, int $limit = 10): array
    {
        $dayStart = $day->setTime(0, 0);

        return $this->createQueryBuilder('activity')
            ->andWhere('activity.scheduledAt >= :dayStart')
            ->andWhere('activity.scheduledAt < :dayEnd')
            ->setParameter('dayStart', $dayStart)
            ->setParameter('dayEnd', $dayStart->modify('+1 day'))
            ->orderBy('activity.scheduledAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
