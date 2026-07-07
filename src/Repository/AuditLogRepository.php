<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * @param array{actor?: mixed, action?: mixed, entityClass?: mixed, from?: mixed, to?: mixed} $filters
     *
     * @return list<AuditLog>
     */
    public function search(array $filters, int $limit = 80): array
    {
        $qb = $this->createQueryBuilder('auditLog')
            ->addSelect('actor')
            ->leftJoin('auditLog.actor', 'actor')
            ->orderBy('auditLog.createdAt', 'DESC')
            ->setMaxResults($limit);

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<string>
     */
    public function findDistinctActions(): array
    {
        return array_column($this->createQueryBuilder('auditLog')
            ->select('DISTINCT auditLog.action')
            ->orderBy('auditLog.action', 'ASC')
            ->getQuery()
            ->getScalarResult(), 'action');
    }

    /**
     * @return list<string>
     */
    public function findDistinctEntityClasses(): array
    {
        return array_column($this->createQueryBuilder('auditLog')
            ->select('DISTINCT auditLog.entityClass')
            ->orderBy('auditLog.entityClass', 'ASC')
            ->getQuery()
            ->getScalarResult(), 'entityClass');
    }

    /**
     * @param array{actor?: mixed, action?: mixed, entityClass?: mixed, from?: mixed, to?: mixed} $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        $actor = $filters['actor'] ?? null;
        if (is_numeric($actor) && (int) $actor > 0) {
            $qb
                ->andWhere('actor.id = :auditActor')
                ->setParameter('auditActor', (int) $actor);
        }

        $action = is_string($filters['action'] ?? null) ? trim($filters['action']) : '';
        if ($action !== '') {
            $qb
                ->andWhere('auditLog.action = :auditAction')
                ->setParameter('auditAction', mb_strtoupper($action));
        }

        $entityClass = is_string($filters['entityClass'] ?? null) ? trim($filters['entityClass']) : '';
        if ($entityClass !== '') {
            $qb
                ->andWhere('auditLog.entityClass = :auditEntityClass')
                ->setParameter('auditEntityClass', $entityClass);
        }

        $from = $this->createDateBoundary($filters['from'] ?? null, false);
        if ($from !== null) {
            $qb
                ->andWhere('auditLog.createdAt >= :auditFrom')
                ->setParameter('auditFrom', $from);
        }

        $to = $this->createDateBoundary($filters['to'] ?? null, true);
        if ($to !== null) {
            $qb
                ->andWhere('auditLog.createdAt <= :auditTo')
                ->setParameter('auditTo', $to);
        }
    }

    private function createDateBoundary(mixed $value, bool $endOfDay): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        return $endOfDay ? $date->setTime(23, 59, 59) : $date;
    }
}
