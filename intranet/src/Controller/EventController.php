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
        $form = $this->createForm(CreateEventFormType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('admin');
        }

        $formView = $form->createView();
        return $this->render('event/event.html.twig', [
            'eventForm' => $formView,
        ]);
    }

    // TODO: deve registrare e deregistrare l'utente all'evento
    //       aggiungendo il collegamento o togliendolo nel DB

    #[Route('/userpage/event/registration/{id}', name:'userpage_event_registration')]
    public function registration(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        $event->setRegistered(true);
        $em->persist($event); // opzionale, ma sicuro
        $em->flush();
        return $this->render('personal/personal.html.twig');
    }
    
    #[Route('/userpage/event/deregistration/{id}', name:'userpage_event_deregistration')]
    public function deregistration(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        $event->setRegistered(false);
        $em->persist($event); // opzionale, ma sicuro
        $em->flush();
        return $this->render('personal/personal.html.twig');
    }
}
