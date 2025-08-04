<?php

namespace App\Service;

use App\Entity\EvalSlot;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EvalSlotService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Returns all open slots (future endTime) excluding the given user.
     */
    public function getOpenSlots(User $user): array
    {
        $now = new \DateTime();

        return $this->em->getRepository(EvalSlot::class)
            ->createQueryBuilder('e')
            ->where('e.endTime > :now')
            ->andWhere('e.userId != :user')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

?>