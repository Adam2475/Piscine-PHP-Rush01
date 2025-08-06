<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use App\Service\SearchBarService;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications', name: 'api_notifications', methods: ['GET'])]
    public function getNotifications(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user)
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        $notifications = $user->getNotifications();
        
        if ($notifications)
        {
            usort($notifications, function($a, $b) {
                $timestampA = is_array($a) && isset($a['timestamp']) ? $a['timestamp'] : '1970-01-01 00:00:00';
                $timestampB = is_array($b) && isset($b['timestamp']) ? $b['timestamp'] : '1970-01-01 00:00:00';
                return strcmp($timestampB, $timestampA);
            });
        }
        $unreadCount = $user->getUnreadNotificationsCount();
        return $this->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/api/notifications/mark-read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markNotificationsAsRead(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user)
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $user->setUnreadNotificationsCount(0);
        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/notifications', name: 'app_notifications_page', methods: ['GET'])]
    public function notificationsPage(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user)
            return $this->redirectToRoute('userpage', ['id' => $user->getId()]);
        
        if ($user->getUnreadNotificationsCount() > 0)
        {
            $user->setUnreadNotificationsCount(0);
            $em->flush();
        }
        $notifications = $user->getNotifications();
        
        if ($notifications)
        {
            usort($notifications, function($a, $b) {
                $timestampA = is_array($a) && isset($a['timestamp']) ? $a['timestamp'] : '1970-01-01 00:00:00';
                $timestampB = is_array($b) && isset($b['timestamp']) ? $b['timestamp'] : '1970-01-01 00:00:00';
                return strcmp($timestampB, $timestampA);
            });
        }
        return $this->render('notifications/index.html.twig', [
            'user' => $user,
            'notifications' => $notifications,
            'unreadCount' => $user->getUnreadNotificationsCount(),
            'originalUser' => $user
        ]);
    }

    #[Route('/notifications/remove/{index}', name: 'app_notifications_remove', methods: ['POST'])]
    public function removeNotification(int $index, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user)
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        $user->removeNotification($index);
        $em->flush();
        return $this->json(['success' => true, 'index' => $index]);
    }

    #[Route('/api/notifications/delete-selected', name: 'api_notifications_delete_selected', methods: ['POST'])]
    public function deleteSelectedNotifications(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user)
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        
        $data = json_decode($request->getContent(), true);
        $indicesToDelete = $data['indices'] ?? [];
        
        if (empty($indicesToDelete)) {
            return $this->json(['error' => 'No notifications selected'], Response::HTTP_BAD_REQUEST);
        }
        
        $notifications = $user->getNotifications();
        if (!$notifications) {
            return $this->json(['error' => 'No notifications found'], Response::HTTP_NOT_FOUND);
        }
        
        // Sort indices in descending order to avoid index shifting issues
        rsort($indicesToDelete);
        
        foreach ($indicesToDelete as $index) {
            if (isset($notifications[$index])) {
                array_splice($notifications, $index, 1);
            }
        }
        
        $user->setNotifications($notifications);
        $user->setUnreadNotificationsCount(max(0, $user->getUnreadNotificationsCount() - count($indicesToDelete)));
        $em->flush();
        
        return $this->json([
            'success' => true, 
            'message' => count($indicesToDelete) . ' notification(s) deleted successfully',
            'deletedCount' => count($indicesToDelete)
        ]);
    }

    
}