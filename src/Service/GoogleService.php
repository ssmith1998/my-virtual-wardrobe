<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessTokenInterface;

class GoogleService
{

public function __construct(
        private readonly Client $googleClient,
        private readonly EntityManagerInterface $entityManager
    ) {
    }
    public function saveUser(GoogleUser $googleUser, AccessTokenInterface $accessToken): User
    {
            /** @var string $email */
            $email = $googleUser->getEmail();
            /** @var string $googleId */
            $googleId = $googleUser->getId();
            /** @var string $name */
            $name = $googleUser->getName();

            $refreshToken = $accessToken->getRefreshToken();
            $tokenExpiry = $accessToken->getExpires();


            // Find existing user or create new one
            /** @var User|null $user */
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);

            if (!$user) {
                // Check if email already exists
                /** @var User|null $existingUser */
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    // Link Google account to existing user
                    $existingUser->setGoogleId($googleId);
                    if ($refreshToken) {
                        $existingUser->setGoogleRefreshToken($refreshToken);
                    }
                    $existingUser->setGoogleAccessTokenExpiry((new \DateTime())->setTimestamp($tokenExpiry));
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleId);
                    $user->setRoles(['ROLE_USER']);
                    if ($refreshToken) {
                        $user->setGoogleRefreshToken($refreshToken);
                    }
                    $user->setGoogleAccessTokenExpiry((new \DateTime())->setTimestamp($tokenExpiry));
                    // For Google users, we don't set a password since they authenticate via OAuth
                }
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

        return $user;
    }

    public function refreshAccessToken(User $user): ?array
    {
        $tokenHasExpired = $user->isGoogleAccessTokenExpired();
        $refreshToken = $user->getGoogleRefreshToken();
        if (!$refreshToken || !$tokenHasExpired) {
            return null;
        }

        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
        $this->googleClient->refreshToken($refreshToken);

        return $this->googleClient->getAccessToken();
    }

    public function getGoogleClientForUser(User $user): Client
    {
        $accessToken = $this->refreshAccessToken($user);

        if ($accessToken) {
            $this->googleClient->setAccessToken($accessToken);
        } else {
            // If the token hasn't expired, we can use the existing access token
            $this->googleClient->setAccessToken([
                'access_token' => $accessToken,
                'expires_in' => $user->getGoogleAccessTokenExpiry()->getTimestamp() - time(),
            ]);
        }

        return $this->googleClient;
    }

    public function isGoogleCalendarIntegrationEnabled(User $user): bool
    {
        return $user->getGoogleId() !== null && $user->getGoogleRefreshToken() !== null;
    }

}