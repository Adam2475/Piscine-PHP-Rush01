<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Agenda;
use App\Entity\Event;
use DateTime;

/**
 * Summary of AgendaController
 * - the index makes a general render of the agenda
 * 		with a twig that loops in the event array 
 */
final class AgendaController extends AbstractController
{
	#[Route('/agenda', name: 'agenda')]
	public function index(): Response
	{
		$event = new Event();
		$agenda = new Agenda();
		$event->setName('Meet');
		$event->setTitle('Submit Rush');
		$event->setMaxParticipants(10);
		$event->setParticipants(0);
		$event->setDate(new DateTime('2025-08-05'));
		$start = clone $event->getDate();
		$start->setTime(14,0,0);
		$event->setStartTime($start);
		$end = clone $event->getDate();
		$end->setTime(16,0,0);
		$event->setEndTime($end);
		$event->setParticipants(0);
		$event->setDuration(2.00);
		$agenda->addEvent($event);
		return $this->render('agenda/agenda.html.twig', [
			'controller_name' => 'Agenda',
			'events'=> $agenda->getEvents(),
		]);
	}
}
