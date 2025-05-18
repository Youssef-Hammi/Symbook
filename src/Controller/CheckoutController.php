<?php

namespace App\Controller;

use App\Form\CheckoutFormType;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(Request $request, Security $security, CartItemRepository $cartItemRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();

        if (!$user) {
            // Redirect to login if user is not authenticated
            return $this->redirectToRoute('app_login');
        }

        $cartItems = $cartItemRepository->findBy(['user' => $user]);

        if (empty($cartItems)) {
            // Redirect to cart if it's empty
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cartitem_cart');
        }

        $totalPrice = 0;
        foreach ($cartItems as $item) {
            // Ensure book is not null before accessing properties
            if ($item->getBook()) {
                 $totalPrice += $item->getBook()->getPrice() * $item->getQuantity();
            }
        }

        $form = $this->createForm(CheckoutFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Create a new Order entity and populate it with data
            $order = new Order();
            $order->setUser($user);
            $order->setTotalPrice($totalPrice);
            // Set shipping details from the form data
            $order->setShippingName($data['shippingName']);
            $order->setShippingAddress($data['shippingAddress']);
            $order->setShippingCity($data['shippingCity']);
            $order->setShippingPostalCode($data['shippingPostalCode']);
            // Set payment method and initial status
            $order->setPaymentMethod($data['paymentMethod']);
            $order->setStatus('pending'); // Initial status for all new orders
            $order->setCreatedAt(new \DateTimeImmutable());

            // Persist the Order entity
            $entityManager->persist($order);

            // Create OrderItem entities from CartItem entities and clear the cart
            foreach ($cartItems as $cartItem) {
                // Ensure book is not null before creating OrderItem
                if ($cartItem->getBook()) {
                    $orderItem = new \App\Entity\OrderItem();
                    // Link OrderItem to the Book and the newly created Order
                    $orderItem->setBook($cartItem->getBook());
                    $orderItem->setOrder($order); // Corrected setter
                    // Set quantity and price (capturing price at the time of order)
                    $orderItem->setQuantity($cartItem->getQuantity());
                    $orderItem->setPrice($cartItem->getBook()->getPrice());

                    // Persist the OrderItem
                    $entityManager->persist($orderItem);

                    // Remove the CartItem as it's now part of an Order
                    $entityManager->remove($cartItem);
                }
            }

            // Flush all changes to the database (persist and remove operations)
            $entityManager->flush();

            // Handle redirection based on payment method
            if ($data['paymentMethod'] === 'delivery') {
                $this->addFlash('success', 'Votre commande a été passée avec succès (Paiement à la livraison).');
                // Redirect to the order history after successful order placement for delivery
                return $this->redirectToRoute('order_history');
            } elseif ($data['paymentMethod'] === 'card') {
                // For card payment, redirect to the dedicated card payment route
                $this->addFlash('info', 'Proceeding to card payment...');
                return $this->redirectToRoute('app_checkout_card', ['orderId' => $order->getId()]);
            }
        }

        return $this->render('checkout/index.html.twig', [
            'checkoutForm' => $form->createView(),
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
        ]);
    }

    #[Route('/checkout/card/{orderId}', name: 'app_checkout_card', methods: ['GET', 'POST'])]
    public function cardPayment(int $orderId, Security $security, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $security->getUser();
        $order = $orderRepository->find($orderId);

        // Check if order exists and belongs to the current user
        if (!$order || $order->getUser() !== $user) {
            $this->addFlash('error', 'Commande introuvable ou accès non autorisé.');
            return $this->redirectToRoute('order_history'); // Or another appropriate page
        }

        // Check if the order is already paid or not in a state to be paid
        if ($order->getStatus() !== 'pending') {
             $this->addFlash('warning', 'Cette commande ne peut pas être payée par carte pour le moment.');
            return $this->redirectToRoute('order_history');
        }

        // Initialize Stripe
        \Stripe\Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        try {
            // Create a PaymentIntent with the order amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int)($order->getTotalPrice() * 100), // Amount in cents
                'currency' => 'eur', // Change currency if needed
                 'metadata' => ['order_id' => $order->getId()],
            ]);

             $this->addFlash('info', 'Veuillez entrer vos informations de carte.');

            return $this->render('checkout/card.html.twig', [
                'order' => $order,
                'clientSecret' => $paymentIntent->client_secret,
                'stripePublicKey' => $_SERVER['STRIPE_PUBLIC_KEY'], // Assuming public key is in .env
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'initialisation du paiement: ' . $e->getMessage());
            // Optionally update order status to failed or similar
            return $this->redirectToRoute('app_checkout'); // Redirect back to checkout or cart
        }
    }

    // TODO: Add a webhook endpoint to handle Stripe events (like payment_intent.succeeded)
} 