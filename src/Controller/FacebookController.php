<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Security;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class FacebookController extends AbstractController
{
    /**
     * @Route("/connect/facebook", name="connect_facebook_start")
     */
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('facebook')
            ->redirect();
    }

    /**
     * @Route("/connect/facebook/check", name="connect_facebook_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, Security $security): RedirectResponse
    {
        $client = $clientRegistry->getClient('facebook');

        try {
            $facebookUser = $client->fetchUser();
            
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $facebookUser->getEmail()]);

            if (!$user) {
                $user = new User();
                $user->setEmail($facebookUser->getEmail());
                $user->setPassword('');
                $user->setIsVerified(true);
                $user->setRoles(['ROLE_USER']);

                $entityManager->persist($user);
                $entityManager->flush();
            }

            $user->setIsVerified(true);
            $entityManager->flush();

            $security->login($user, App\Security\AuthAuthenticator::class, 'main');

            return $this->redirectToRoute('app_book_showcase');

        } catch (IdentityProviderException $e) {
            return $this->redirectToRoute('app_login');
        }
    }
} 