<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ExperienceService
{
    private EntityManagerInterface $em;

    // injecting parameter entity manager
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Returns how much XP is needed to reach the next level.
     */
    public function getXpForNextLevel(int $level): int
    {
        return (($level * 1000) + (($level - 1) * 1000));
    }

    /**
     * Returns XP progress percentage towards next level.
     */
    public function getProgressPercent(User $user): float
    {
        $needed = $this->getXpForNextLevel($user->getLevel());
        if ($needed <= 0) {
            return 0;
        }

        return min(100, ($user->getExperience() / $needed) * 100);
    }

    /**
     * Returns remaining XP to next level.
     */
    public function getXpRemaining(User $user): int
    {
        return $this->getXpForNextLevel($user->getLevel()) - $user->getExperience();
    }

    public function addExperience(User $user, int $xp): void
    {
        $currentXp = $user->getExperience();
        $currentLevel = $user->getLevel();

        $currentXp += $xp;

        // Loop to handle multiple level-ups if needed
        while ($currentXp >= $this->getXpForNextLevel($currentLevel)) {
            $xpForNext = $this->getXpForNextLevel($currentLevel);
            $currentXp -= $xpForNext;
            $currentLevel++;
        }

        // Set new values

        // var_dump($currentXp, $currentLevel);
        $user->setLevel($currentLevel);
        // $user->setExperience($currentXp);

        // Persist and flush changes to the database
        $this->em->persist($user);
        $this->em->flush();
    }
}

?>