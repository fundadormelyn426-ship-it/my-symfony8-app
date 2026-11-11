<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(ProductRepository $productRepo): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'productCount' => count($productRepo->findAll()),
        ]);
    }

    #[Route('/products', name: 'dashboard_products', methods: ['GET'])]
    public function products(ProductRepository $productRepo): Response
    {
        return $this->render('dashboard/products/index.html.twig', [
            'products' => $productRepo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/products/new', name: 'dashboard_products_new', methods: ['GET', 'POST'])]
    public function productNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $product = new Product();
            $product->setName($request->request->get('name'));
            $product->setPrice((float)$request->request->get('price'));
            $product->setDescription($request->request->get('description'));
            $product->setImage($request->request->get('image'));
            $product->setCategory($request->request->get('category'));
            $product->setIsAvailable($request->request->get('isAvailable') ? true : false);
            $product->setStock((int)$request->request->get('stock', 0));

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product added successfully!');
            return $this->redirectToRoute('dashboard_products');
        }

        return $this->render('dashboard/products/form.html.twig', [
            'product' => null,
        ]);
    }

    #[Route('/products/{id}/edit', name: 'dashboard_products_edit', methods: ['GET', 'POST'])]
    public function productEdit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('name'));
            $product->setPrice((float)$request->request->get('price'));
            $product->setDescription($request->request->get('description'));
            $product->setImage($request->request->get('image'));
            $product->setCategory($request->request->get('category'));
            $product->setIsAvailable($request->request->get('isAvailable') ? true : false);
            $product->setStock((int)$request->request->get('stock', 0));

            $em->flush();

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('dashboard_products');
        }

        return $this->render('dashboard/products/form.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/products/{id}/delete', name: 'dashboard_products_delete', methods: ['POST'])]
    public function productDelete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted successfully!');
        return $this->redirectToRoute('dashboard_products');
    }

    #[Route('/orders', name: 'dashboard_orders', methods: ['GET'])]
    public function orders(OrderRepository $orderRepo): Response
    {
        return $this->render('dashboard/orders/index.html.twig', [
            'orders' => $orderRepo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/orders/{id}/status', name: 'dashboard_orders_status', methods: ['POST'])]
    public function orderStatusUpdate(
        \App\Entity\Order $order,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $newStatus = $request->request->get('status');
        $allowedStatuses = ['Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled'];
        
        if (in_array($newStatus, $allowedStatuses)) {
            $order->setStatus($newStatus);
            $em->flush();
            $this->addFlash('success', 'Order #' . $order->getId() . ' status updated to ' . $newStatus);
        } else {
            $this->addFlash('error', 'Invalid status.');
        }
        
        return $this->redirectToRoute('dashboard_orders');
    }

    #[Route('/users', name: 'dashboard_users', methods: ['GET'])]
    public function users(UserRepository $userRepo): Response
    {
        $users = $userRepo->findBy([], ['id' => 'DESC']);
        
        $adminCount = 0;
        foreach ($users as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount++;
            }
        }
        
        return $this->render('dashboard/users/index.html.twig', [
            'users' => $users,
            'adminCount' => $adminCount,
            'newThisWeek' => 0,
        ]);
    }
}