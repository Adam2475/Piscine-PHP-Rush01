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
use App\Form\RemoveEventType;

final class EventController extends AbstractController
{

	// Add an event to the agenda
	// TODO: vedere se ha realmente senso passare un user se poi devo fare find by ID
	#[Route('/admin/event/new', name: 'admin_event_new')]
	public function new(Request $request, EntityManagerInterface $em): Response
	{
		$event = new Event();
		$form = $this->createForm(CreateEventFormType::class, $event);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$startTime = $form->get('startTime')->getData();
			$endTime = $form->get('endTime')->getData();
			// Calcolo la differenza
			$interval = $startTime->diff($endTime);		
			// Converto in float (es. 1 ora 30 min => 1.5)
			$hours = (float) $interval->h + $interval->i / 60;
			$hoursForm = number_format($hours,2);
			$event->setDuration($hoursForm);
			$em->persist($event);
			foreach ($em->getRepository(User::class)->findAll() as $user)
			{
				$user->addNotification('A new event has been created: ' . $event->getTitle());
				$em->persist($user);
			}
			$em->flush();
			$this->addFlash('success', 'Event created successfully!');
			
			return $this->redirectToRoute('admin_event_new');
		}

		$formView = $form->createView();
		return $this->render('event/new_event.html.twig', [
			'eventForm' => $formView,
		]);
	}

	// TODO: Remove an event from the agenda
	#[Route('/admin/event/remove', name:'admin_event_remove')]
	public function edit(Request $request, EntityManagerInterface $em): Response
	{
		$form = $this->createForm(RemoveEventType::class);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$events = $form->get('events')->getData();
	   		foreach ($events as $event)
				$em->remove($event);
			$em->flush();
			$this->addFlash('success', 'Eventi eliminati');
			return $this->redirectToRoute('admin_event_remove');
		}

		return $this->render('event/remove.html.twig', [
			'form' => $form->createView(),
		]);
	}

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
		if ($event->getParticipants() == $event->getMaxParticipants()){
			$this->addFlash('error','This Event is full! Too late...');
			return $this->redirectToRoute('userpage', ['id' => $user_id]);
		}
		$event->addUser($user);
		$numParticipants = count($event->getUsers());
		$event->setParticipants($numParticipants);
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
		if ($event->getUsers()->contains($user) == true) {
			$event->removeUser($user);
			$numParticipants = count($event->getUsers());
			$event->setParticipants($numParticipants);
			$em->flush();
		}
		return $this->redirectToRoute('userpage', ['id' => $user_id]);
	}
}
