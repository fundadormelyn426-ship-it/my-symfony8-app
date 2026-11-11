<?php

namespace App\Controller;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReceiptController extends AbstractController
{
    #[Route('/receipt/{id}', name: 'app_receipt', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Kung dili admin ug dili tag-iya sa order, deny
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles()) 
            && $order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot view this receipt.');
        }
        
        return $this->render('receipt/show.html.twig', [
            'order' => $order,
        ]);
    }
}