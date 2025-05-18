<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

//    /**
//     * @return Book[] Returns an array of Book objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Book
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * Searches for books by title or author and optionally filters by category.
     *
     * @param string|null $searchTerm The term to search for in title or author.
     * @param int|null $categoryId The category ID to filter by.
     * @return Book[] Returns an array of Book objects.
     */
    public function searchByTermAndCategory(?string $searchTerm, ?int $categoryId): array
    {
        $qb = $this->createQueryBuilder('b');

        if ($searchTerm) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('b.title', ':searchTerm'),
                    $qb->expr()->like('b.author', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($categoryId) {
            $qb->andWhere('b.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }
}
