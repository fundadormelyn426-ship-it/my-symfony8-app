<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my-orders')]
class OrderHistoryController extends AbstractController
{
    #[Route('', name: 'app_my_orders', methods: ['GET'])]
    public function index(OrderRepository $orderRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $orders = $orderRepo->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );
        
        return $this->render('order_history/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_my_order_show', methods: ['GET'])]
    public function show(int $id, OrderRepository $orderRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $order = $orderRepo->findOneBy([
            'id' => $id,
            'user' => $this->getUser(),
        ]);
        
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }
        
        return $this->render('order_history/show.html.twig', [
            'order' => $order,
        ]);
    }
}