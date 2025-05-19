<?php

namespace App\Controller;

use App\Form\CheckoutFormType;
use App\Form\CheckoutShippingType;
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
use App\Repository\BookRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
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

        // Create a new Order entity for the form to collect shipping details
        $order = new Order();
        // Pre-populate user and potentially other fields if you have default user shipping info
        $order->setUser($user);

        // Create the checkout shipping form
        $form = $this->createForm(CheckoutShippingType::class, $order);

        // Handle the form submission
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // The form has already populated the shipping fields on the $order entity

            // Set preliminary order details
            $order->setTotalPrice($totalPrice);
            // Set a temporary status indicating shipping is provided but payment is pending
            $order->setStatus('shipping_provided'); // Use a new or existing status
            $order->setCreatedAt(new \DateTimeImmutable());
            // Payment method is not set here yet
            $order->setPaymentMethod('N/A'); // Or null

            // Persist the preliminary Order entity BEFORE creating OrderItems (to get Order ID)
            $entityManager->persist($order);
            $entityManager->flush(); // Flush now to ensure the order gets an ID

            // Create OrderItem entities from CartItem entities and decrement stock (moved from webhook/finalizeDelivery)
            $cartItemsToProcess = $cartItemRepository->findBy(['user' => $user]); // Fetch cart items for processing

            if (!empty($cartItemsToProcess)) {
                foreach ($cartItemsToProcess as $cartItem) {
                    $book = $cartItem->getBook();
                    if ($book) {
                        $orderItem = new \App\Entity\OrderItem();
                        $orderItem->setBook($book);
                        $orderItem->setOrder($order); // Link to the *current* order
                        $orderItem->setQuantity($cartItem->getQuantity());
                        $orderItem->setPrice($book->getPrice()); // Capture price at order time

                        // Decrement book stock
                        $currentStock = $book->getStock();
                        $purchasedQuantity = $cartItem->getQuantity();
                        $newStock = max(0, $currentStock - $purchasedQuantity);
                        $book->setStock($newStock);

                        $entityManager->persist($book); // Persist updated book (stock change)
                        $entityManager->persist($orderItem); // Persist order item
                        // DO NOT remove cart item here yet - only remove on successful payment confirmation
                    }
                }
                 // Flush pending changes (new order items, updated book stock)
                $entityManager->flush();
            }

            // Redirect to the payment selection page, passing the order ID
            return $this->redirectToRoute('app_checkout_payment', ['orderId' => $order->getId()]);
        }

        // If GET request or form is not submitted/invalid, render the checkout page with the form
        return $this->render('checkout/index.html.twig', [
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
            'shippingForm' => $form->createView(),
        ]);
    }

    // This method will display payment options and initiate payment processes
    #[Route('/checkout/payment/{orderId}', name: 'app_checkout_payment', methods: ['GET'])]
    public function payment(int $orderId, Security $security, OrderRepository $orderRepository): Response
    {
        $user = $security->getUser();

        if (!$user) {
            // Redirect to login if user is not authenticated
            return $this->redirectToRoute('app_login');
        }

        $order = $orderRepository->find($orderId);

        // Check if order exists and belongs to the current user
        if (!$order || $order->getUser() !== $user || $order->getStatus() !== 'shipping_provided') {
            $this->addFlash('error', 'Impossible de procéder au paiement pour cette commande.');
            return $this->redirectToRoute('order_history'); // Or another appropriate page
        }

        // Render the payment selection page
        return $this->render('checkout/payment.html.twig', [
            'order' => $order,
        ]);
    }

    // This method will finalize the order for 'Pay at Delivery'
    #[Route('/checkout/finalize-delivery/{orderId}', name: 'app_checkout_finalize_delivery', methods: ['POST'])]
    public function finalizeDelivery(int $orderId, Security $security, OrderRepository $orderRepository, CartItemRepository $cartItemRepository, EntityManagerInterface $entityManager, BookRepository $bookRepository): Response
    {
         $user = $security->getUser();

        if (!$user) {
            // Redirect to login if user is not authenticated
            return $this->redirectToRoute('app_login');
        }

        $order = $orderRepository->find($orderId);

        // Check if order exists, belongs to the user, and is in the correct status
        if (!$order || $order->getUser() !== $user || $order->getStatus() !== 'shipping_provided') {
             $this->addFlash('error', 'Impossible de finaliser cette commande.');
            return $this->redirectToRoute('order_history');
        }

        // Set final order details for Pay at Delivery
        $order->setPaymentMethod('delivery');
        $order->setStatus('pending'); // Or 'processing'

        // Create OrderItem entities from CartItem entities and clear the cart (logic moved from index)
        $cartItems = $cartItemRepository->findBy(['user' => $user]); // Need to fetch cart items again or pass them
         if (empty($cartItems)) {
            // Cart became empty between steps, handle accordingly
            $this->addFlash('warning', 'Votre panier est vide. La commande ne peut pas être finalisée.');
            // Maybe redirect to the order that was just created (even if empty)? Or history?
             // For now, redirect to history
             return $this->redirectToRoute('order_history');
        }

        foreach ($cartItems as $cartItem) {
            $book = $cartItem->getBook();
            if ($book) {
                $orderItem = new \App\Entity\OrderItem();
                $orderItem->setBook($book);
                $orderItem->setOrder($order); // Link to the *current* order
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($book->getPrice()); // Capture price at order time

                // Decrement book stock
                $currentStock = $book->getStock();
                $purchasedQuantity = $cartItem->getQuantity();
                $newStock = max(0, $currentStock - $purchasedQuantity);
                $book->setStock($newStock);

                $entityManager->persist($book); // Persist updated book
                $entityManager->persist($orderItem); // Persist order item
                $entityManager->remove($cartItem); // Remove cart item
            }
        }

        // Flush all changes
        $entityManager->flush();

        // Add success flash message and redirect to order history
        $this->addFlash('success', 'Votre commande a été passée avec succès (Paiement à la livraison).');
        return $this->redirectToRoute('order_history');
    }

    #[Route('/checkout/card/{orderId}', name: 'app_checkout_card', methods: ['GET', 'POST'])]
    public function cardPayment(int $orderId, Security $security, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $security->getUser();
        $order = $orderRepository->find($orderId);

        // Check if order exists and belongs to the current user and is in the correct status
        if (!$order || $order->getUser() !== $user || $order->getStatus() !== 'shipping_provided') {
            $this->addFlash('error', 'Impossible de procéder au paiement par carte pour cette commande.');
            return $this->redirectToRoute('order_history'); // Or another appropriate page
        }

        // Set the payment method to 'card' immediately when the user chooses this option
        // NOTE: While we set it here for immediate display, the webhook handler is the authoritative source.
        $order->setPaymentMethod('card');
        $entityManager->flush(); // Persist the payment method change

        // The Order entity already exists and has shipping details. Now proceed with Stripe.

        // Initialize Stripe
        \Stripe\Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        try {
            // Create a PaymentIntent with the order amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int)($order->getTotalPrice() * 100), // Amount in cents
                'currency' => 'eur', // Change currency if needed
                 'metadata' => ['order_id' => $order->getId()],
                 'setup_future_usage' => 'on_session', // Optional: save card for future
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
            // Since the order is already created (preliminary), you might want to update its status here
            // $order->setStatus('payment_failed');
            // $entityManager->flush();
            return $this->redirectToRoute('app_checkout', ['orderId' => $order->getId()]); // Redirect back to checkout with the same order?
        }
    }

    // TODO: Implement Stripe webhook endpoint (for production readiness)
    // #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    // public function stripeWebhook(Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager, CartItemRepository $cartItemRepository, BookRepository $bookRepository, LoggerInterface $logger): Response
    // {
    //     // This method's logic has been moved to StripeWebhookController::index
    //     // The route annotation is commented out to prevent routing conflicts
    //     throw new \LogicException('This webhook endpoint should not be reached directly.');
    // }
} 