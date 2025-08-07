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
        $conversationPartners = $this->createQueryBuilder('m')
            ->select('
                IDENTITY(m.recipient) as recipient_id,
                IDENTITY(m.sender) as sender_id,
                MAX(m.createdAt) as last_message_time
            ')
            ->where('(m.sender = :user OR m.recipient = :user)')
            ->andWhere('m.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'private')
            ->groupBy('m.recipient, m.sender')
            ->orderBy('last_message_time', 'DESC')
            ->setMaxResults($limit * 2)
            ->getQuery()
            ->getResult();

        $uniquePartners = [];
        foreach ($conversationPartners as $conv) {
            $partnerId = ($conv['sender_id'] == $user->getId()) ? $conv['recipient_id'] : $conv['sender_id'];
            
            if (!isset($uniquePartners[$partnerId])) {
                $uniquePartners[$partnerId] = $conv['last_message_time'];
            }
        }

        arsort($uniquePartners);
        $uniquePartners = array_slice($uniquePartners, 0, $limit, true);

        $conversations = [];
        foreach ($uniquePartners as $partnerId => $lastTime) {
            $latestMessage = $this->createQueryBuilder('m')
                ->select('m.content, m.mediaUrl, m.mediaName, m.createdAt, u.first_name, u.last_name, u.email, u.image')
                ->leftJoin('App\Entity\User', 'u', 'WITH', 'u.id = :partnerId')
                ->where('((m.sender = :user AND m.recipient = :partnerId) OR (m.sender = :partnerId AND m.recipient = :user))')
                ->andWhere('m.type = :type')
                ->setParameter('user', $user)
                ->setParameter('partnerId', $partnerId)
                ->setParameter('type', 'private')
                ->orderBy('m.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($latestMessage) {
                $unreadCount = $this->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->where('m.sender = :partnerId AND m.recipient = :user AND m.isRead = false')
                    ->setParameter('partnerId', $partnerId)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult();

                $conversations[] = [
                    'recipient_id' => $partnerId,
                    'name' => $latestMessage['first_name'] . ' ' . $latestMessage['last_name'],
                    'email' => $latestMessage['email'],
                    'image' => $latestMessage['image'],
                    'last_message' => $this->formatMessagePreview($latestMessage['content'], $latestMessage['mediaUrl'], $latestMessage['mediaName']),
                    'last_message_time' => $latestMessage['createdAt'],
                    'unread_count' => $unreadCount
                ];
            }
        }

        return $conversations;
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

    /**
     * Format message preview for conversation list
     */
    private function formatMessagePreview(?string $content, ?string $mediaUrl, ?string $mediaName): string
    {
        if ($mediaUrl) {
            $extension = pathinfo($mediaUrl, PATHINFO_EXTENSION);
            $lowerExt = strtolower($extension);
        
            if (in_array($lowerExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                return '📷 Image';
            elseif (in_array($lowerExt, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']))
                return '🎥 Video';
            elseif (in_array($lowerExt, ['mp3', 'wav', 'ogg', 'aac', 'flac']))
                return '🎵 Audio';
            elseif (in_array($lowerExt, ['pdf']))
                return '📄 PDF';
            elseif (in_array($lowerExt, ['doc', 'docx']))
                return '📝 Document';
            else
                return '📎 File';
        }
        
        // Se c'è solo testo, limitalo a 50 caratteri
        if ($content) {
            $content = trim($content);
            if (strlen($content) > 50) {
                return substr($content, 0, 50) . '...';
            }
            return $content;
        }
        
        return 'No messages yet';
    }
}
