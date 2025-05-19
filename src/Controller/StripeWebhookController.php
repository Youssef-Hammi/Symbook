<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\CartItemRepository;
use App\Repository\BookRepository;
use Psr\Log\LoggerInterface;

class StripeWebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function index(Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager, CartItemRepository $cartItemRepository, BookRepository $bookRepository, LoggerInterface $logger): JsonResponse
    {
        $logger->info('Stripe webhook received by StripeWebhookController.');

        $endpointSecret = $_SERVER['STRIPE_WEBHOOK_SECRET'] ?? null;
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if (!$endpointSecret) {
             $logger->error('Webhook secret not configured.');
             return new JsonResponse(['error' => 'Webhook secret not configured.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $signature, $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            $logger->error('Invalid payload: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Invalid payload.'], Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $logger->error('Invalid signature: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Invalid signature.'], Response::HTTP_BAD_REQUEST);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $logger->info('Webhook event: payment_intent.succeeded');
                $paymentIntent = $event->data->object;
                // Handle successful payment, e.g., fulfill the order
                
                $orderId = $paymentIntent->metadata->order_id ?? null;
                $logger->info('Processing PaymentIntent with order_id: ' . $orderId);

                if ($orderId) {
                    $order = $orderRepository->find($orderId);

                    if ($order) {
                        $logger->info('Order found. Current status: ' . $order->getStatus());
                        // Only process if order exists and is not already completed/paid
                        if ($order->getStatus() !== 'completed' && $order->getStatus() !== 'paid') {
                            $logger->info('Order status is not completed or paid. Proceeding with update.');
                            // Update order status and payment method
                            $order->setStatus('paid'); // Update order status
                            $logger->info('Order status set to paid.');
                            $order->setPaymentMethod('card'); // Set payment method
                            $logger->info('Order payment method set to card.');

                            // Now clear the cart for the user associated with this order
                            $user = $order->getUser();
                            $logger->info('Fetching user for order. User ID: ' . ($user ? $user->getId() : 'N/A'));

                            if ($user) {
                                $cartItems = $cartItemRepository->findBy(['user' => $user]);
                                $logger->info('Found ' . count($cartItems) . ' cart items for user.');

                                foreach ($cartItems as $cartItem) {
                                    $logger->info('Removing cart item ID: ' . $cartItem->getId());
                                    $entityManager->remove($cartItem);
                                }
                                $logger->info('Finished removing cart items.');

                                // Flush changes: order status/method and cart clearing
                                $entityManager->flush();
                                $logger->info('EntityManager flushed after order update and cart clearing.');
                            } else {
                                $logger->warning('User not found for order ID: ' . $orderId . '. Cannot clear cart.');
                            }
                        } else {
                            $logger->info('Order status is already completed or paid. Skipping update.');
                        }
                         // Consider logging successful order update
                    } else {
                         $logger->warning('Order not found for ID from webhook metadata: ' . $orderId);
                    }
                } else {
                     $logger->warning('Order ID not found in PaymentIntent metadata.');
                }

                break;
            case 'payment_intent.payment_failed':
                $logger->info('Webhook event: payment_intent.payment_failed');
                $paymentIntent = $event->data->object;
                // Handle payment_intent_failed, e.g., update order status to failed
                $orderId = $paymentIntent->metadata->order_id ?? null;
                if ($orderId) {
                    $order = $orderRepository->find($orderId);
                    if ($order) {
                        $order->setStatus('payment_failed');
                        $entityManager->flush();
                        $logger->info('Order status set to payment_failed for order ID: ' . $orderId);
                    } else {
                        $logger->warning('Order not found for ID from failed payment intent metadata: ' . $orderId);
                    }
                }
                break;
            // ... handle other event types like charge.succeeded, refund.created, etc.
            default:
                // Unexpected event type
                 $logger->info('Received unexpected event type: ' . $event->type);
        }

        return new JsonResponse(['status' => 'success']);
    }
} 