<?php

namespace App\Repository;

use App\Entity\EvalSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvalSlot>
 */
class EvalSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvalSlot::class);
    }

    /**
     * Find available evaluation slots (excluding the current user's slots)
     */
    public function findAvailableSlots($excludeUser): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('App\Entity\ProjectEvaluationRequest', 'per', 'WITH', 'per.evaluator = e.userId AND per.validated = false')
            ->andWhere('e.userId != :user')
            ->andWhere('per.id IS NULL') // slot owner is not currently evaluating anything
            ->andWhere('e.endTime > :now') // Only slots that haven't ended yet
            ->setParameter('user', $excludeUser)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete expired evaluation slots
     */
    public function deleteExpiredSlots(): int
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.endTime < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * Find slots for a specific user
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :user')
            ->setParameter('user', $user)
            ->orderBy('e.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
