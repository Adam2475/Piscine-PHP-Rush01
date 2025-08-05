<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\ChatMessageRepository;
use App\Repository\UserProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    #[Route('/', name: 'chat_index')]
    public function index(ChatMessageRepository $chatRepo, UserRepository $userRepo): Response
    {
        $user = $this->getUser();
        $conversations = $chatRepo->getRecentConversations($user);
        $unreadCount = $chatRepo->getUnreadCount($user);

        return $this->render('chat/index.html.twig', [
            'conversations' => $conversations,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/private/{recipientId}', name: 'chat_private')]
    public function privateChat(int $recipientId, UserRepository $userRepo, ChatMessageRepository $chatRepo): Response
    {
        $user = $this->getUser();
        $recipient = $userRepo->find($recipientId);

        if (!$recipient)
            throw $this->createNotFoundException('User not found');
        $messages = $chatRepo->getPrivateMessages($user, $recipient);
        $chatRepo->markAsRead($recipient, $user);

        return $this->render('chat/private.html.twig', [
            'recipient' => $recipient,
            'messages' => $messages,
        ]);
    }

    #[Route('/project/{projectId}', name: 'chat_project')]
    public function projectChat(int $projectId, ProjectRepository $projectRepo, ChatMessageRepository $chatRepo, UserProjectRepository $userProjectRepository): Response
    {
        $user = $this->getUser();
        $project = $projectRepo->find($projectId);

        if (!$project)
            throw $this->createNotFoundException('Project not found');
        $userProject = $userProjectRepository->findOneBy([
            'user' => $user,
            'project' => $project,
        ]);
        if (!$userProject)
            throw $this->createAccessDeniedException('You are not authorized to access this chat');
        $messages = $chatRepo->getProjectMessages($project);

        return $this->render('chat/project.html.twig', [
            'project' => $project,
            'messages' => $messages,
        ]);
    }

    #[Route('/api/send', name: 'chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em, UserRepository $userRepo, ProjectRepository $projectRepo, UserProjectRepository $userProjectRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user)
            return $this->json(['error' => 'User not authenticated'], 401);
        $content = $request->request->get('content');
        $mediaFile = $request->files->get('media');

        if ((!$content || empty(trim($content))) && !$mediaFile)
            return $this->json(['error' => 'Message or file required'], 400);
        $message = new ChatMessage();
        $message->setSender($user);
        if ($content)
            $message->setContent(trim($content));
        else
            $message->setContent('');
        if ($mediaFile)
        {
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/chat';

            if (!is_dir($uploadsDirectory))
                mkdir($uploadsDirectory, 0755, true);
            $fileName = uniqid() . '.' . $mediaFile->guessExtension();
            $mediaFile->move($uploadsDirectory, $fileName);
            $message->setMediaUrl('/uploads/chat/' . $fileName);
            $message->setMediaName($mediaFile->getClientOriginalName());
        }
        $type = $request->request->get('type', 'private');
        if ($type === 'project')
        {
            $projectId = $request->request->get('projectId');

            if (!$projectId)
                return $this->json(['error' => 'Project ID required'], 400);
            $project = $projectRepo->find($projectId);

            if (!$project)
                return $this->json(['error' => 'Project not found'], 404);
            $userProject = $userProjectRepository->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);
            if (!$userProject)
                return $this->json(['error' => 'Access denied for this project chat'], 403);
            $message->setProject($project);
            $message->setType('project');
            $allUserProjects = $userProjectRepository->findBy(['project' => $project]);
            foreach ($allUserProjects as $projUsers)
            {
                $participant = $projUsers->getUser();
                if ($participant->getId() !== $user->getId() && !$participant->getIsActive())
                {
                    $participant->addNotification('New message in project "' . $project->getName() . '" from ' . $user->getFirstName() . ' ' . $user->getLastName());
                    $em->persist($participant);
                }
            }
        }
        else
        {
            $recipientId = $request->request->get('recipientId');

            if (!$recipientId)
                return $this->json(['error' => 'Recipient ID required'], 400);
            $recipient = $userRepo->find($recipientId);

            if (!$recipient)
                return $this->json(['error' => 'Recipient not found'], 404);
            $message->setRecipient($recipient);
            $message->setType('private');
            // if (!$recipient->getIsActive())
            // {
                $recipient->addNotification('You have a new message from ' . $user->getFirstName() . ' ' . $user->getLastName());
                $em->persist($recipient);
            // }
        }
        $em->persist($message);
        $em->flush();

        return $this->json([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'mediaUrl' => $message->getMediaUrl(),
            'mediaName' => $message->getMediaName(),
            'sender' => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/messages/private/{recipientId}', name: 'chat_get_private_messages', methods: ['GET'])]
    public function getPrivateMessages(int $recipientId, UserRepository $userRepo, ChatMessageRepository $chatRepo): JsonResponse
    {
        $user = $this->getUser();
        $recipient = $userRepo->find($recipientId);

        if (!$recipient)
            return $this->json(['error' => 'Recipient not found'], 404);
        $messages = $chatRepo->getPrivateMessages($user, $recipient);
        $chatRepo->markAsRead($recipient, $user);
        $messagesData = array_map(function ($message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'mediaUrl' => $message->getMediaUrl(),
                'mediaName' => $message->getMediaName(),
                'sender' => [
                    'id' => $message->getSender()->getId(),
                    'firstName' => $message->getSender()->getFirstName(),
                    'lastName' => $message->getSender()->getLastName(),
                ],
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $messages);

        return $this->json($messagesData);
    }

    #[Route('/api/messages/project/{projectId}', name: 'chat_get_project_messages', methods: ['GET'])]
    public function getProjectMessages(int $projectId,ProjectRepository $projectRepo,ChatMessageRepository $chatRepo,UserProjectRepository $userProjectRepository): JsonResponse
    {
        $user = $this->getUser();
        $project = $projectRepo->find($projectId);

        if (!$project)
            return $this->json(['error' => 'Project not found'], 404);
        $userProject = $userProjectRepository->findOneBy([
            'user' => $user,
            'project' => $project,
        ]);

        if (!$userProject)
            return $this->json(['error' => 'Access denied for this project chat'], 403);
        $messages = $chatRepo->getProjectMessages($project);
        $messagesData = array_map(function ($message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'mediaUrl' => $message->getMediaUrl(),
                'mediaName' => $message->getMediaName(),
                'sender' => [
                    'id' => $message->getSender()->getId(),
                    'firstName' => $message->getSender()->getFirstName(),
                    'lastName' => $message->getSender()->getLastName(),
                ],
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $messages);
        return $this->json($messagesData);
    }

    #[Route('/api/unread-count', name: 'chat_unread_count', methods: ['GET'])]
    public function getUnreadCount(ChatMessageRepository $chatRepo): JsonResponse
    {
        $user = $this->getUser();
        $unreadCount = $chatRepo->getUnreadCount($user);

        return $this->json(['unreadCount' => $unreadCount]);
    }
}
