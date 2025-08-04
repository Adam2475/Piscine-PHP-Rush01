<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Get private messages between two users
     */
    public function getPrivateMessages(User $user1, User $user2, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->andWhere('m.type = :type')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('type', 'private')
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get project messages
     */
    public function getProjectMessages(Project $project, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.project = :project')
            ->andWhere('m.type = :type')
            ->setParameter('project', $project)
            ->setParameter('type', 'project')
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent conversations for a user
     */
    public function getRecentConversations(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->select('DISTINCT IDENTITY(m.recipient) as recipient_id, IDENTITY(m.sender) as sender_id, MAX(m.createdAt) as last_message')
            ->where('m.sender = :user OR m.recipient = :user')
            ->andWhere('m.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'private')
            ->groupBy('m.recipient, m.sender')
            ->orderBy('last_message', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(User $sender, User $recipient): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':read')
            ->where('m.sender = :sender AND m.recipient = :recipient AND m.isRead = :unread')
            ->setParameter('read', true)
            ->setParameter('unread', false)
            ->setParameter('sender', $sender)
            ->setParameter('recipient', $recipient)
            ->getQuery()
            ->execute();
    }

    /**
     * Get unread message count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.recipient = :user AND m.isRead = :read')
            ->setParameter('user', $user)
            ->setParameter('read', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
