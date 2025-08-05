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
        $unreadCount = $user->getUnreadNotificationsCount();
        return $this->json([
            'notifications' => array_reverse($notifications),
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
        return $this->render('notifications/index.html.twig', [
            'user' => $user,
            'notifications' => array_reverse($user->getNotifications()),
            'unreadCount' => $user->getUnreadNotificationsCount(),
            'originalUser' => $user
        ]);
    }
}