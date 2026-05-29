<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/api/auth/google', name: 'connect_google_start', methods: ['GET'])]
    public function connect(): RedirectResponse
    {
        return $this->clientRegistry
            ->getClient('google')
            ->redirect(['openid', 'profile', 'email'], []);
    }

    #[Route('/api/auth/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function connectCheck(): JsonResponse
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');

        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            /** @var string $email */
            $email = $googleUser->getEmail();
            /** @var string $googleId */
            $googleId = $googleUser->getId();
            /** @var string $name */
            $name = $googleUser->getName();

            // Find existing user or create new one
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);

            if (!$user) {
                // Check if email already exists
                /** @var User|null $existingUser */
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    // Link Google account to existing user
                    $existingUser->setGoogleId($googleId);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleId);
                    $user->setRoles(['ROLE_USER']);
                    // For Google users, we don't set a password since they authenticate via OAuth
                }
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $name,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication failed: ' . $e->getMessage()], 400);
        }
    }
}
