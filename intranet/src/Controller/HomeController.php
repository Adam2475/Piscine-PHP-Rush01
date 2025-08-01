<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        // Get the logged-in user (or null if not logged in)
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'user' => $user,
        ]);
    }
}
