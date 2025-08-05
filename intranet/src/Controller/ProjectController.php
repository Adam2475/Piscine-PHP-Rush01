<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\UserProject;
use App\Entity\ProjectEvaluationRequest;
use App\Entity\EvalSlot;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\UserProjectRepository;
use App\Repository\ProjectEvaluationRequestRepository;
use App\Repository\EvalSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/project')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'project_list')]
    public function list(ProjectRepository $projectRepository): Response
    {
        $projects = $projectRepository->findAll();

        return $this->render('project/list.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/{id}', name: 'project_show')]
    public function show(
        Project $project,
        UserProjectRepository $userProjectRepository,
        ProjectEvaluationRequestRepository $evaluationRequestRepository,
        EvalSlotRepository $evalSlotRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $userProject = null;
        $evaluationRequest = null;
        $availableSlots = [];

        // Clean up expired evaluation slots
        $expiredCount = $evalSlotRepository->deleteExpiredSlots();
        if ($expiredCount > 0) {
            $em->flush(); // Ensure the deletions are committed
        }

        if ($user) {
            $userProject = $userProjectRepository->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);

            // Check if user has submitted for evaluation
            if ($userProject && $userProject->getUploadedFilePath()) {
                $evaluationRequest = $evaluationRequestRepository->findByRequesterAndProject($user, $project);
                
                // If evaluation request exists but no evaluator assigned, show available slots
                if ($evaluationRequest && !$evaluationRequest->getEvaluator()) {
                    $availableSlots = $evalSlotRepository->findAvailableSlots($user);
                }
            }
        }

        // ✅ Get validated user projects
        $completedParticipants = $project->getUserProjects()->filter(
            fn(UserProject $up) => $up->isValidated()
        );

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'userProject' => $userProject,
            'evaluationRequest' => $evaluationRequest,
            'availableSlots' => $availableSlots,
            'completedParticipants' => $completedParticipants,
        ]);
    }


    #[Route('/{id}/register', name: 'project_register')]
    public function register(Project $project, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user) {
            // Check if UserProject already exists to avoid duplicates
            $existing = $em->getRepository(UserProject::class)->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);
            if (!$existing) {
                $userProject = new UserProject();
                $userProject->setUser($user);
                $userProject->setProject($project);
                $em->persist($userProject);
                $em->flush();
            }
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/unregister', name: 'project_unregister', methods: ['POST'])]
    public function unregister(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user) {
            $userProject = $em->getRepository(UserProject::class)->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);
            if ($userProject) {
                $em->remove($userProject);
                $em->flush();
            }
        }

        $redirectTo = $request->request->get('redirect_to', 'project');

        if ($redirectTo === 'userpage') {
            return $this->redirectToRoute('userpage', ['id' => $user->getId()]);
        } else {
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }
    }

    #[Route('/{id}/upload', name: 'project_upload', methods: ['POST'])]
    public function upload(
        Project $project,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to upload files.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        // Check if user has enough evaluation points
        if ($user->getEvalPoints() < 1) {
            $this->addFlash('error', 'You need at least 1 evaluation point to submit a project for evaluation.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $userProject = $em->getRepository(UserProject::class)->findOneBy([
            'user' => $user,
            'project' => $project,
        ]);

        if (!$userProject) {
            $this->addFlash('error', 'You must register for the project first.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('project_file');

        if ($uploadedFile) {
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            try {
                $uploadDir = $this->getParameter('project_files_directory');
                $uploadedFile->move($uploadDir, $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload file.');
                return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
            }

            $userProject->setUploadedFilePath($newFilename);
            $em->persist($userProject);

            // Create evaluation request
            $evaluationRequest = new ProjectEvaluationRequest();
            $evaluationRequest->setRequester($user);
            $evaluationRequest->setProject($project);
            
            $em->persist($evaluationRequest);
            $em->flush();

            $this->addFlash('success', 'Project file uploaded and submitted for evaluation.');
        } else {
            $this->addFlash('error', 'No file uploaded.');
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/select-evaluator/{slotId}', name: 'project_select_evaluator', methods: ['POST'])]
    public function selectEvaluator(
        Project $project,
        int $slotId,
        EvalSlotRepository $evalSlotRepository,
        ProjectEvaluationRequestRepository $evaluationRequestRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $evalSlot = $evalSlotRepository->find($slotId);
        if (!$evalSlot) {
            $this->addFlash('error', 'Evaluation slot not found.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        // Check if the evaluation slot has expired
        if ($evalSlot->getEndTime() < new \DateTime()) {
            // Delete the expired slot
            $em->remove($evalSlot);
            $em->flush();
            $this->addFlash('error', 'The selected evaluation slot has expired and has been removed.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $evaluationRequest = $evaluationRequestRepository->findByRequesterAndProject($user, $project);
        if (!$evaluationRequest) {
            $this->addFlash('error', 'No evaluation request found.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $evaluationRequest->setEvaluator($evalSlot->getUserId());
        // Don't set the evalSlot reference since we're going to delete it immediately
        
        $em->persist($evaluationRequest);
        $em->flush();

        // Delete the evaluation slot immediately after selection
        $em->remove($evalSlot);
        $em->flush();

        $this->addFlash('success', 'Evaluator selected successfully. You will be notified when the evaluation is complete.');
        
        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/evaluation/{id}/validate', name: 'project_evaluate', methods: ['POST'])]
    public function evaluateProject(
        int $id,
        Request $request,
        ProjectEvaluationRequestRepository $evaluationRequestRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $evaluationRequest = $evaluationRequestRepository->find($id);
        if (!$evaluationRequest || $evaluationRequest->getEvaluator() !== $user) {
            $this->addFlash('error', 'You are not authorized to evaluate this project.');
            return $this->redirectToRoute('project_list');
        }

        $approved = $request->request->get('approved') === '1';
        
        if ($approved) {
            $userProject = $evaluationRequest->getUserProject($em);
            if (!$userProject) {
                $this->addFlash('error', 'UserProject not found for this evaluation.');
                return $this->redirectToRoute('evaluations');
            }
            
            $userProject->setValidated(true);
            $userProject->setValidatedBy($user);
            
            // Add XP to the requester
            $requester = $evaluationRequest->getRequester();
            $requester->addExperience($evaluationRequest->getProject()->getXp());
            
            // Deduct 1 evaluation point from requester (user being validated)
            $requester->setEvalPoints($requester->getEvalPoints() - 1);
            
            // Add 1 evaluation point to evaluator
            $user->setEvalPoints($user->getEvalPoints() + 1);
            
            $evaluationRequest->setValidated(true);
            $evaluationRequest->setEvaluatedAt(new \DateTime());
            
            $em->persist($userProject);
            $em->persist($requester);
            $em->persist($user);
            $em->persist($evaluationRequest);
            
            $this->addFlash('success', 'Project approved successfully! +1 evaluation point earned.');
        } else {
            // Project rejected - user can resubmit
            // Still deduct evaluation point from requester and add to evaluator
            $requester = $evaluationRequest->getRequester();
            $requester->setEvalPoints($requester->getEvalPoints() - 1);
            $user->setEvalPoints($user->getEvalPoints() + 1);
            
            $evaluationRequest->setEvaluator(null);
            $evaluationRequest->setEvaluatedAt(new \DateTime());            $em->persist($requester);
            $em->persist($user);
            $em->persist($evaluationRequest);
            
            $this->addFlash('info', 'Project needs more work. The requester can resubmit. +1 evaluation point earned.');
        }
        
        $em->flush();
        
        return $this->redirectToRoute('evaluations');
    }

    #[Route('/evaluation/{id}', name: 'project_evaluation_show')]
    public function showEvaluation(
        int $id,
        ProjectEvaluationRequestRepository $evaluationRequestRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $evaluationRequest = $evaluationRequestRepository->find($id);
        if (!$evaluationRequest || $evaluationRequest->getEvaluator() !== $user) {
            $this->addFlash('error', 'You are not authorized to view this evaluation.');
            return $this->redirectToRoute('evaluations');
        }

        // Get the UserProject for the evaluation
        $userProject = $evaluationRequest->getUserProject($em);

        return $this->render('project/evaluate.html.twig', [
            'evaluation' => $evaluationRequest,
            'userProject' => $userProject,
        ]);
    }

    #[Route('/projects/{id}/validate/{userId}', name: 'project_validate_user')]
    public function validateProject(
        int $id,
        int $userId,
        ProjectRepository $projectRepository,
        UserRepository $userRepository,
        UserProjectRepository $userProjectRepository,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $project = $projectRepository->find($id);
        $user = $userRepository->find($userId);
        $currentUser = $security->getUser();

        $userProject = $userProjectRepository->findOneBy([
            'user' => $user,
            'project' => $project,
        ]);

        if (!$userProject) {
            throw $this->createNotFoundException('UserProject not found');
        }

        if ($userProject->isValidated()) {
            $this->addFlash('info', 'This project was already validated.');
        } else {
            $userProject->setValidated(true);
            $userProject->setValidatedBy($currentUser);
            $em->flush();

            $this->addFlash('success', 'Project validated!');
        }

        return $this->redirectToRoute('admin_project_overview', ['id' => $id]);
    }

}
