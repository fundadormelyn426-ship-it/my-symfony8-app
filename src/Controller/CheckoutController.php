<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CartService $cart,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // Require login
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Kung empty ang cart, redirect sa cart page
        if ($cart->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $email = trim($request->request->get('email', ''));
            $paymentMethod = $request->request->get('payment_method', 'Cash');
            $gcashReference = trim($request->request->get('gcash_reference', ''));

            // Validation
            if (!$name || !$email) {
                $error = 'Please fill in all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif ($paymentMethod === 'GCash' && !$gcashReference) {
                $error = 'Please enter your GCash reference number.';
            } else {
                // CHECK STOCK
                $stockError = null;
                foreach ($cart->getCart() as $item) {
                    $product = $em->getRepository(Product::class)->find($item['id']);
                    if (!$product) {
                        $stockError = 'Product not found: ' . $item['name'];
                        break;
                    }
                    if ($product->getStock() < $item['quantity']) {
                        $stockError = 'Not enough stock for ' . $product->getName() 
                            . '. Available: ' . $product->getStock() 
                            . ', Requested: ' . $item['quantity'];
                        break;
                    }
                }

                if ($stockError) {
                    $error = $stockError;
                } else {
                    // Create order
                    $order = new Order();
                    $order->setTotal($cart->getTotal());
                    $order->setStatus('Pending');
                    $order->setPaymentMethod($paymentMethod);
                    $order->setGcashReference($paymentMethod === 'GCash' ? $gcashReference : null);
                    $order->setCreatedAt(new \DateTimeImmutable());
                    $order->setUser($this->getUser());

                    $em->persist($order);

                    // Create order items
                    foreach ($cart->getCart() as $item) {
                        $orderItem = new OrderItem();
                        $orderItem->setProductName($item['name']);
                        $orderItem->setPrice($item['price']);
                        $orderItem->setQuantity($item['quantity']);
                        $orderItem->setSubtotal($item['price'] * $item['quantity']);
                        $orderItem->setCustomerOrder($order);

                        $product = $em->getRepository(Product::class)->find($item['id']);
                        if ($product) {
                            $orderItem->setProduct($product);
                            
                            // DEDUCT STOCK
                            $newStock = $product->getStock() - $item['quantity'];
                            $product->setStock($newStock);
                            
                            if ($newStock <= 0) {
                                $product->setIsAvailable(false);
                            }
                        }

                        $em->persist($orderItem);
                    }

                    $em->flush();

                    // Send confirmation email
                    try {
                        $emailMessage = (new Email())
                            ->from('hello@fundadorcoffeeshop.com')
                            ->to($email)
                            ->subject('Order Confirmation #' . $order->getId() . ' - Fundador Coffee Shop')
                            ->html($this->renderView('emails/order_confirmation.html.twig', [
                                'order' => $order,
                                'name' => $name,
                            ]));

                        $mailer->send($emailMessage);
                    } catch (\Exception $e) {
                        // Log error but don't fail the order
                    }

                    // Clear cart
                    $cart->clear();

                    
                    // Deretso sa Receipt
return $this->redirectToRoute('app_receipt', ['id' => $order->getId()]);
                }
            }
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart->getCart(),
            'total' => $cart->getTotal(),
            'error' => $error,
        ]);
    }

    #[Route('/success/{id}', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Order $order): Response
    {
        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}