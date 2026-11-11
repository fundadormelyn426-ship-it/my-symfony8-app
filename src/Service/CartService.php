<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function getCart(): array
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::SESSION_KEY, []);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::SESSION_KEY, []);
        
        $id = $product->getId();
        
        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'id' => $id,
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'image' => $product->getImage(),
                'quantity' => $quantity,
            ];
        }
        
        $session->set(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::SESSION_KEY, []);
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $session->set(self::SESSION_KEY, $cart);
        }
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::SESSION_KEY, []);
        
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } elseif (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
        }
        
        $session->set(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_KEY);
    }

    public function getCount(): int
    {
        $cart = $this->getCart();
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    public function getTotal(): float
    {
        $cart = $this->getCart();
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }
}