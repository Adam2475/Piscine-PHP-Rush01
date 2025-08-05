<?php

namespace App\Controller;

use App\Entity\EvalSlot;
use App\Entity\ProjectEvaluationRequest;
use App\Form\EvalSlotType;
use App\Repository\ProjectEvaluationRequestRepository;
use App\Repository\EvalSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

class EvalSlotController extends AbstractController
{
    #[Route('/evaluations', name: 'evaluations')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(
        Request $request, 
        EntityManagerInterface $em,
        ProjectEvaluationRequestRepository $evaluationRequestRepository,
        EvalSlotRepository $evalSlotRepository
    ): Response {
        $evalSlot = new EvalSlot();

        $form = $this->createForm(EvalSlotType::class, $evalSlot);
        $form->handleRequest($request);
        $user = $this->getUser();
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $start = $evalSlot->getStartTime();
            $end = $evalSlot->getEndTime();

            if ($this->isSlotOverlapping($start, $end, $this->getUser(), $em))
            {
                $this->addFlash('error', 'The selected time overlaps with an existing evaluation slot.');
                return $this->redirectToRoute('evaluations');
            }

            $evalSlot->setUserId($this->getUser());

            $em->persist($evalSlot);
            $em->flush();

            $this->addFlash('success', 'Evaluation slot created successfully.');

            return $this->redirectToRoute('evaluations');
        }

        // Get user's evaluation slots
        $userSlots = $evalSlotRepository->findByUser($user);
        
        // Get pending evaluations assigned to this user
        $pendingEvaluations = $evaluationRequestRepository->findBy([
            'evaluator' => $user,
            'validated' => false
        ]);

        return $this->render('personal/eval.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'userSlots' => $userSlots,
            'pendingEvaluations' => $pendingEvaluations,
        ]);
    }

    // Inside your controller method
    public function isSlotOverlapping(\DateTime $newStart, \DateTime $newEnd, User $user, EntityManagerInterface $em): bool
    {
        $qb = $em->createQueryBuilder();

        $qb->select('e')
            ->from(EvalSlot::class, 'e')
            ->where('e.userId = :user')
            ->andWhere('e.startTime < :newEnd')
            ->andWhere('e.endTime > :newStart')
            ->setParameter('user', $user)
            ->setParameter('newStart', $newStart)
            ->setParameter('newEnd', $newEnd);

        $overlappingSlots = $qb->getQuery()->getResult();

        return !empty($overlappingSlots);
    }
}