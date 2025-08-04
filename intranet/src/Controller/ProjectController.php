<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\UserProject;
use App\Repository\ProjectRepository;
use App\Repository\UserProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        UserProjectRepository $userProjectRepository
    ): Response {
        $user = $this->getUser();
        $userProject = null;

        if ($user) {
            $userProject = $userProjectRepository->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);
        }

        // ✅ Get validated user projects
        $completedParticipants = $project->getUserProjects()->filter(
            fn(UserProject $up) => $up->isValidated()
        );

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'userProject' => $userProject,
            'completedParticipants' => $completedParticipants, // 👈 pass it to Twig
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

            // Se non è gia stato validato, aggiunge xp all'utente
            if (!$userProject->isValidated()) {
                $userProject->setValidated(true);
                $user->addExperience($project->getXp());

                $em->persist($user);
            }

            $userProject->setUploadedFilePath($newFilename);

            $em->persist($userProject);
            $em->flush();

            $this->addFlash('success', 'Project file uploaded and validated.');
        } else {
            $this->addFlash('error', 'No file uploaded.');
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }
}
