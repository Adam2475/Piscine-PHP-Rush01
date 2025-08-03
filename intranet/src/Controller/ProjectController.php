<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

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
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/register', name: 'project_register')]
    public function register(Project $project, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user && !$user->getProjects()->contains($project)) {
            $user->addProject($project);
            $project->addParticipant($user);  // <-- Add this line

            $em->flush();
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/unregister', name: 'project_unregister', methods: ['POST'])]
    public function unregister(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user && $user->getProjects()->contains($project)) {
            $user->removeProject($project);
            $project->removeParticipant($user);  // <-- Add this line

            $em->flush();
        }

        $redirectTo = $request->request->get('redirect_to', 'project');

        if ($redirectTo === 'userpage') {
            return $this->redirectToRoute('userpage', ['id' => $user->getId()]);
        } else {
            // redirect to project show page
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }
    }
}
