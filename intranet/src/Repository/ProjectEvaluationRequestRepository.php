<?php

namespace App\Repository;

use App\Entity\ProjectEvaluationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectEvaluationRequest>
 */
class ProjectEvaluationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectEvaluationRequest::class);
    }

    /**
     * Find pending evaluation requests for a specific project
     */
    public function findPendingForProject($project): array
    {
        return $this->createQueryBuilder('per')
            ->andWhere('per.project = :project')
            ->andWhere('per.validated = false')
            ->andWhere('per.evaluator IS NULL')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find evaluation requests where a user is the evaluator
     */
    public function findByEvaluator($user): array
    {
        return $this->createQueryBuilder('per')
            ->andWhere('per.evaluator = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find evaluation request by requester and project
     */
    public function findByRequesterAndProject($user, $project): ?ProjectEvaluationRequest
    {
        return $this->createQueryBuilder('per')
            ->andWhere('per.requester = :user')
            ->andWhere('per.project = :project')
            ->setParameter('user', $user)
            ->setParameter('project', $project)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
