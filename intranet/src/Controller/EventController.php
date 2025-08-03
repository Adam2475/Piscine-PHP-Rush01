<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\CreateEventFormType;

final class EventController extends AbstractController
{

    // Add an event to the agenda
    #[Route('/admin/event/new', name: 'admin_event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(CreateEventFormType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('admin');
        }

        $formView = $form->createView();
        return $this->render('event/new_event.html.twig', [
            'eventForm' => $formView,
        ]);
    }

    // TODO: Remove an event from the agenda
    #[Route('/admin/event/remove', name:'admine_event_remve')]

    // subscribe and unsubscribe ft

    #[Route('/userpage/event/registration/{event_id}/{user_id}', name:'userpage_event_registration')]
    public function registration(int $event_id, int $user_id, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Event::class)->find($event_id);
        $user = $em->getRepository(User::class)->find($user_id);
        if (!$event || !$user) {
            $this->addFlash('error','User or Event not found');
            return $this->redirectToRoute('userpage', ['id' => $user_id]);
        }
        if ($event->getParticipants() < 0)
            $event->setParticipants(0);
        if ($event->getParticipants() == $event->getMaxParticipants()){
            $this->addFlash('error','This Event is full! Too late...');
            return $this->redirectToRoute('userpage', ['id' => $user_id]);
        }
        $event->addUser($user);
        $event->setParticipants($event->getParticipants() + 1);
        $event->setRegistered(true);
        $this->addFlash('success', 'You are now registered to the event!');
        $em->flush();
        return $this->redirectToRoute('userpage', ['id' => $user_id]);
    }
    
    #[Route('/userpage/event/deregistration/{event_id}/{user_id}', name:'userpage_event_deregistration')]
    public function deregistration(int $event_id, int $user_id, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Event::class)->find($event_id);
        $user = $em->getRepository(User::class)->find($user_id);
        if (!$event || !$user) {
            $this->addFlash('error','User or Event not found');
            return $this->redirectToRoute('userpage', ['id' => $user_id]);
        }
        if ($event->getParticipants() < 0)
            $event->setParticipants(0);
        if ($event->isRegistered() == true) {
            $event->removeUser($user);
            $event->setParticipants($event->getParticipants() - 1);
            $event->setRegistered(false);
            $this->addFlash('success', 'You are no longer registered to the event!');
            $em->flush();
        }
        return $this->redirectToRoute('userpage', ['id' => $user_id]);
    }
}
