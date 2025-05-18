<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Form\CartItemType;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\BookRepository;
use App\Entity\Book;

#[Route('/cart')]
class CartItemController extends AbstractController
{
    #[Route('/item/', name: 'app_cart_item_index', methods: ['GET'])]
    public function index(CartItemRepository $cartItemRepository): Response
    {
        return $this->render('cart_item/index.html.twig', [
            'cart_items' => $cartItemRepository->findAll(),
        ]);
    }

    #[Route('/item/new', name: 'app_cart_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cartItem = new CartItem();
        $form = $this->createForm(CartItemType::class, $cartItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cartItem);
            $entityManager->flush();

            return $this->redirectToRoute('app_cart_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('cart_item/new.html.twig', [
            'cart_item' => $cartItem,
            'form' => $form,
        ]);
    }

    #[Route('/item/{id}', name: 'app_cart_item_show', methods: ['GET'])]
    public function show(CartItem $cartItem): Response
    {
        return $this->render('cart_item/show.html.twig', [
            'cart_item' => $cartItem,
        ]);
    }

    #[Route('/item/{id}/edit', name: 'app_cart_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CartItem $cartItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CartItemType::class, $cartItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cart_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('cart_item/edit.html.twig', [
            'cart_item' => $cartItem,
            'form' => $form,
        ]);
    }

    #[Route('/item/{id}', name: 'app_cart_item_delete', methods: ['POST'])]
    public function delete(Request $request, CartItem $cartItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cartItem->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cartItem);
            $entityManager->flush();
        }

        return $this->redirectToRoute('cartitem_cart', [], Response::HTTP_SEE_OTHER);
    }

    public function add(int $bookId, BookRepository $bookRepository, Security $security, EntityManagerInterface $em): RedirectResponse
    {
        $user = $security->getUser();
        $book = $bookRepository->find($bookId);
        if (!$user || !$book) {
            return $this->redirectToRoute('app_book_showcase');
        }
        $cartItem = new CartItem();
        $cartItem->setUser($user);
        $cartItem->setBook($book);
        $cartItem->setQuantity(1);
        $em->persist($cartItem);
        $em->flush();
        return $this->redirectToRoute('cartitem_cart');
    }

    #[Route('', name: 'cartitem_cart', methods: ['GET', 'POST'])]
    public function cart(Security $security, CartItemRepository $cartItemRepository)
    {
        $user = $security->getUser();
        $items = $cartItemRepository->findBy(['user' => $user]);
        return $this->render('cart_item/cart.html.twig', [
            'items' => $items
        ]);
    }

    #[Route('/item/{id}/update-quantity/{action}', name: 'cartitem_update_quantity', methods: ['POST'])]
    public function updateQuantity(int $id, string $action, CartItemRepository $cartItemRepository, EntityManagerInterface $em): Response
    {
        $cartItem = $cartItemRepository->find($id);
        if ($cartItem) {
            if ($action === 'increase') {
                $cartItem->setQuantity($cartItem->getQuantity() + 1);
            } elseif ($action === 'decrease' && $cartItem->getQuantity() > 1) {
                $cartItem->setQuantity($cartItem->getQuantity() - 1);
            }
            $em->flush();
        }
        return $this->redirectToRoute('cartitem_cart');
    }

    #[Route('/item/add/{bookId}', name: 'cartitem_add', methods: ['POST'])]
    public function addToCart(int $bookId, Security $security, EntityManagerInterface $em, BookRepository $bookRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $security->getUser();
        $book = $bookRepository->find($bookId);

        if (!$book) {
            throw $this->createNotFoundException('Book not found.');
        }

        $cartItem = $em->getRepository(CartItem::class)->findOneBy(['user' => $user, 'book' => $book]);

        if ($cartItem) {
            // Item already in cart, increase quantity
            $cartItem->setQuantity($cartItem->getQuantity() + 1);
        } else {
            // Item not in cart, create new cart item
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setBook($book);
            $cartItem->setQuantity(1);
        }

        $em->persist($cartItem);
        $em->flush();

        $this->addFlash('success', 'Book added to cart!');

        return $this->redirectToRoute('app_book_showcase');
    }
}
