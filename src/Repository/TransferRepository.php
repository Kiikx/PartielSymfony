<?php

namespace App\Repository;

use App\Entity\Building;
use App\Entity\Transfer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transfer>
 */
class TransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transfer::class);
    }

    /**
     * @return list<Transfer>
     */
    public function findRecent(?Building $building = null, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('transfer')
            ->orderBy('transfer.scheduledAt', 'DESC')
            ->setMaxResults($limit);

        if ($building !== null) {
            $qb->leftJoin('transfer.fromCell', 'fromCell')
                ->leftJoin('fromCell.wing', 'fromWing')
                ->leftJoin('transfer.toCell', 'toCell')
                ->leftJoin('toCell.wing', 'toWing')
                ->andWhere('fromWing.building = :building OR toWing.building = :building')
                ->setParameter('building', $building);
        }

        return $qb->getQuery()->getResult();
    }
}
