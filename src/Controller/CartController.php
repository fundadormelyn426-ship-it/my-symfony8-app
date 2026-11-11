<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Repository\OrderRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(CartService $cart): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cart->getCart(),
            'total' => $cart->getTotal(),
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, CartService $cart): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $cart->add($product, $quantity);

        $this->addFlash('success', $product->getName() . ' added to cart!');

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('app_cart'));
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id, CartService $cart): Response
    {
        $cart->remove($id);
        $this->addFlash('success', 'Item removed from cart.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cart): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $cart->updateQuantity($id, $quantity);
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(CartService $cart): Response
    {
        $cart->clear();
        $this->addFlash('success', 'Cart cleared.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        CartService $cart,
        EntityManagerInterface $em
    ): Response {
        if ($cart->isEmpty()) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        if ($request->isMethod('POST')) {
            $order = new Order();
            $order->setTotal($cart->getTotal());
            $order->setStatus('Pending');
            $order->setPaymentMethod($request->request->get('payment_method', 'Cash'));
            $order->setCreatedAt(new \DateTimeImmutable());

            if ($this->getUser()) {
                $order->setUser($this->getUser());
            }

            $em->persist($order);

            foreach ($cart->getCart() as $item) {
                $orderItem = new OrderItem();
                $orderItem->setProductName($item['name']);
                $orderItem->setPrice($item['price']);
                $orderItem->setQuantity($item['quantity']);
                $orderItem->setSubtotal($item['price'] * $item['quantity']);
                $orderItem->setCustomerOrder($order);

                $em->persist($orderItem);
            }

            $em->flush();

            $cart->clear();

            $this->addFlash('success', 'Order placed successfully! Order #' . $order->getId());
            return $this->redirectToRoute('app_cart_success', ['id' => $order->getId()]);
        }

        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart->getCart(),
            'total' => $cart->getTotal(),
        ]);
    }

    #[Route('/success/{id}', name: 'app_cart_success', methods: ['GET'])]
    public function success(int $id, OrderRepository $orderRepo): Response
    {
        $order = $orderRepo->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('cart/success.html.twig', [
            'order' => $order,
        ]);
    }
}