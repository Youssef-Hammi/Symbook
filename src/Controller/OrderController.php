<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Repository\CartItemRepository;
use App\Entity\OrderItem;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/history', name: 'order_history', methods: ['GET'])]
    public function history(Security $security, OrderRepository $orderRepository, LoggerInterface $logger)
    {
        $user = $security->getUser();
        // Log to check if this method is reached
        $logger->info('Accessing order history for user: ' . ($user ? $user->getUserIdentifier() : 'anonymous'));
        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        // Log the number of orders found
        $logger->info('Found ' . count($orders) . ' orders for the user.');
        return $this->render('order/history.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    public function checkout(Security $security, CartItemRepository $cartItemRepository, EntityManagerInterface $em, MailerInterface $mailer)
    {
        $user = $security->getUser();
        $cartItems = $cartItemRepository->findBy(['user' => $user]);
        if (!$cartItems) {
            return $this->redirectToRoute('cartitem_cart');
        }
        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus('pending');
        $order->setPaymentMethod('stripe');
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setBook($cartItem->getBook());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice($cartItem->getBook()->getPrice());
            $em->persist($orderItem);
            $total += $orderItem->getPrice() * $orderItem->getQuantity();
            $em->remove($cartItem);
        }
        $order->setTotalPrice($total);
        $em->persist($order);
        $em->flush();
        // Envoi email confirmation
        $email = (new Email())
            ->from('noreply@symbook.com')
            ->to($user->getEmail())
            ->subject('Confirmation de votre commande')
            ->html('<p>Merci pour votre commande n°' . $order->getId() . '.</p><p>Total : ' . $order->getTotalPrice() . ' €</p>');
        $mailer->send($email);
        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }
}
