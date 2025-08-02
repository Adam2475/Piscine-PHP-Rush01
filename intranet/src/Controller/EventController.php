<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Event;
use App\Entity\Agenda;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\CreateEventFormType;

final class EventController extends AbstractController
{
    // funzione non utilizzata
    #[Route('/event', name: 'app_event')]
    public function index(): Response
    {
        return $this->render('event/event.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }

    #[Route('/admin/event/new', name: 'admin_event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();

        // Prendi l'agenda unica dal DB
        $agenda = $em->getRepository(Agenda::class)->findOneBy([]);

        // Associa subito l'agenda all'evento
        $event->setAgenda($agenda);
        // $agenda->addEvent($event);

        $form = $this->createForm(CreateEventFormType::class, $event);

        $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $em->persist($event);
        //     $em->flush();

        //     return $this->redirectToRoute('agenda');
        // }

        // return $this->render('event/event.html.twig', [
        //     'form' => $form->createView(),
        // ]);
        if ($form->isSubmitted() && $form->isValid()) {
            // Set agenda or other required fields if needed
            // $newEvent->setAgenda($someAgenda);

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('admin');
        }

        $formView = $form->createView();

        // Render admin page here
        return $this->render('personal/admin.html.twig', [
            'eventForm' => $formView,
        ]);
    }
}
