<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogleAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect();
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheckAction(): Response
    {
        // This route is handled by the GoogleAuthenticator
        return $this->redirectToRoute('app_book_showcase');
    }

    #[Route('/connect/facebook', name: 'connect_facebook_start')]
    public function connectFacebookAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('facebook')->redirect();
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectFacebookCheckAction(): Response
    {
        // This route is handled by the FacebookAuthenticator
        return $this->redirectToRoute('app_book_showcase');
    }

    #[Route('/connect/github', name: 'connect_github_start')]
    public function connectGithubAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('github')->redirect();
    }

    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function connectGithubCheckAction(): Response
    {
        // This route is handled by the GithubAuthenticator
        return $this->redirectToRoute('app_book_showcase');
    }
} 