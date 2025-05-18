<?php

namespace App\Controller\Admin;

use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(OrderItemRepository $orderItemRepository, OrderRepository $orderRepository, UserRepository $userRepository, BookRepository $bookRepository, CategoryRepository $categoryRepository): Response
    {
        // Total number of users
        $totalUsers = $userRepository->count([]);

        // Number of admins
        $totalAdmins = $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->getQuery()
            ->getSingleScalarResult();

        // Total number of books
        $totalBooks = $bookRepository->count([]);

        // Total number of categories
        $totalCategories = $categoryRepository->count([]);

        // Total number of orders
        $totalOrders = $orderRepository->count([]);

        // Total income (sum of total_price from all orders)
        $totalIncome = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->getQuery()
            ->getSingleScalarResult();

        // Total number of books sold
        $totalBooksSold = $orderItemRepository->createQueryBuilder('oi')
            ->select('SUM(oi.quantity)')
            ->getQuery()
            ->getSingleScalarResult();

        // Most sold category
        $mostSoldCategory = $orderItemRepository->createQueryBuilder('oi')
            ->select('c.name, SUM(oi.quantity) as totalQuantity')
            ->join('oi.book', 'b')
            ->join('b.category', 'c')
            ->groupBy('c.name')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Average Order Value
        $averageOrderValue = $totalOrders > 0 ? $totalIncome / $totalOrders : 0;

        // Number of orders by status (assuming you have a 'status' field in your Order entity)
        // You might need to adjust the statuses based on your actual Order entity.
        $ordersByStatus = $orderRepository->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as orderCount')
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        // Livre le plus vendu
        $topBook = $orderItemRepository->createQueryBuilder('oi')
            ->select('b, oi, SUM(oi.quantity) as total')
            ->join('oi.book', 'b')
            ->groupBy('b')
            ->orderBy('total', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        // Nombre de commandes par mois
        $orders = $orderRepository->findAll(); // Fetch all orders

        $ordersByMonth = [];
        foreach ($orders as $order) {
            $createdAt = $order->getCreatedAt();
            if ($createdAt) {
                $year = $createdAt->format('Y');
                $month = $createdAt->format('m');
                $key = $year . '-' . $month; // Use year-month as key

                if (!isset($ordersByMonth[$key])) {
                    $ordersByMonth[$key] = ['year' => (int)$year, 'month' => (int)$month, 'count' => 0];
                }
                $ordersByMonth[$key]['count']++;
            }
        }

        // Sort by year and then month
        ksort($ordersByMonth);

        // Re-index the array for easier Twig processing if needed, or just pass as is
        $ordersByMonth = array_values($ordersByMonth);

        return $this->render('admin/dashboard.html.twig', [
            'topBook' => $topBook,
            'ordersByMonth' => $ordersByMonth,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalBooks' => $totalBooks,
            'totalCategories' => $totalCategories,
            'totalOrders' => $totalOrders,
            'totalIncome' => $totalIncome,
            'totalBooksSold' => $totalBooksSold,
            'mostSoldCategory' => $mostSoldCategory,
            'averageOrderValue' => $averageOrderValue,
            'ordersByStatus' => $ordersByStatus,
        ]);
    }
} 