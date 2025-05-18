<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Book;
use App\Entity\Category;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $admin = new User();
        $admin->setEmail('admin@symbook.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // Regular user
        $user = new User();
        $user->setEmail('user@symbook.com');
        $user->setRoles([]); // Only ROLE_USER
        $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'));
        $user->setIsVerified(true);
        $manager->persist($user);

        // Create Categories
        $categoriesData = [
            ['name' => 'Science Fiction', 'slug' => 'sf', 'description' => 'Books set in the future or on other planets.'],
            ['name' => 'Fantasy', 'slug' => 'fantasy', 'description' => 'Books with magic and mythical creatures.'],
            ['name' => 'Technology', 'slug' => 'tech', 'description' => 'Books about programming and technology.'],
            ['name' => 'Mystery', 'slug' => 'mystery', 'description' => 'Books with intriguing plots and detectives.'],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setSlug($data['slug']);
            $category->setDescription($data['description']);
            $manager->persist($category);
            $categories[] = $category; // Store created category objects
        }

        // Livres de test
        foreach ([
            [
                'title' => 'Symfony pour les débutants',
                'author' => 'Fabien Potencier',
                'price' => 29.99,
                'description' => 'Un guide complet pour démarrer avec Symfony.',
                'image' => 'https://via.placeholder.com/150',
                'stock' => 10
            ],
            [
                'title' => 'PHP avancé',
                'author' => 'Rasmus Lerdorf',
                'price' => 39.99,
                'description' => 'Approfondissez vos connaissances en PHP.',
                'image' => 'https://via.placeholder.com/150',
                'stock' => 5
            ],
            [
                'title' => 'Architecture Logicielle',
                'author' => 'Robert C. Martin',
                'price' => 49.99,
                'description' => "Les bonnes pratiques de l'architecture logicielle.",
                'image' => 'https://via.placeholder.com/150',
                'stock' => 7
            ],
        ] as $data) {
            $book = new Book();
            $book->setTitle($data['title']);
            $book->setAuthor($data['author']);
            $book->setPrice($data['price']);
            $book->setDescription($data['description']);
            $book->setImage($data['image']);
            $book->setStock($data['stock']);

            // Assign a random category to the book
            $randomCategory = $categories[array_rand($categories)];
            $book->setCategory($randomCategory);

            $manager->persist($book);
        }

        $manager->flush();
    }
}
