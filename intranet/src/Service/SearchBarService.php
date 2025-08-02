<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SearchBarService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Search users by first name, last name, or email.
     */
    public function searchUsers(?string $query): array
    {
        if (!$query) {
            return [];
        }

        return $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search')
            ->setParameter('search', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}

?>