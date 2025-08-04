<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/shop', name: 'shop_index')]
    public function index(): Response
    {
        return $this->render('shop/index.html.twig', [
            'controller_name' => 'ShopController',
        ]);
    }

    #[Route('/shop/buy', name: 'shop_buy', methods: ['POST'])]
    public function buy(Request $request): Response
    {
        $item = $request->request->get('item');

        $this->addFlash('success', "Hai acquistato: $item!");

        return $this->redirectToRoute('shop_index');
    }
}
