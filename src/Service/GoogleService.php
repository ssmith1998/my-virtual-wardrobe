<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Google\Service\Calendar;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Log\LoggerInterface;

class GoogleService
{

public function __construct(
        private readonly Client $googleClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
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
        if (!$refreshToken && !$tokenHasExpired) {
            return null;
        }

        $this->logger->info('Refresh Token: {refreshToken}', ['refreshToken' => $refreshToken]);

        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
        $this->googleClient->refreshToken($refreshToken);

        $this->logger->info('Access token:', ['token' => $this->googleClient->getAccessToken()]);

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
        return $user->isGoogleCalendarIntegrationEnabled();
    }

    public function revokeGoogleCalendarIntegration(User $user): void
    {
        $user->setGoogleId(null);
        $user->setGoogleRefreshToken(null);
        $user->setGoogleAccessTokenExpiry(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Get Google Calendar events for the authenticated user
     * 
     * @param User $user
     * @return array
     */
    public function getGoogleCalendarEvents(User $user): array
    {
        if (!$user->isGoogleCalendarIntegrationEnabled()) {
            return [];
        }

        $client = $this->getGoogleClientForUser($user);
        $service = new Calendar($client);

        $calendarId = 'primary';
        $today = new \DateTime();
        $tomorrow = (clone $today)->modify('+1 day');
        $optParams = [
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $today->format(\DateTime::RFC3339),
            // 'timeMax' => $tomorrow->format(\DateTime::RFC3339),
        ];

        try {
            $results = $service->events->listEvents($calendarId, $optParams);
        } catch (\Exception $e) {
            // Handle the error, e.g., log it or return an empty array
            $this->logger->error('Error fetching Google Calendar events: {error}', ['error' => $e->getMessage()]);
            return [];
        }

        return array_map(function($event) {
            return [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'description' => $event->getDescription(),
                'start' => $event->getStart()->getDate(),
                'end' => $event->getEnd()->getDate(),
                'location' => $event->getLocation(),
            ];
        }, $results->getItems());
    }

    public function eventsToString(array $events): string
    {
        $eventStrings = array_map(function($event) {
            $start = isset($event['start']) ? $event['start'] : 'N/A';
            $end = isset($event['end']) ? $event['end'] : 'N/A';
            return sprintf(
                "Event: %s\nDescription: %s\nStart: %s\nEnd: %s\nLocation: %s\n",
                $event['summary'] ?? 'N/A',
                $event['description'] ?? 'N/A',
                $start,
                $end,
                $event['location'] ?? 'N/A'
            );
        }, $events);

        return implode("\n", $eventStrings);
    }

}