<?php

namespace App\Controller;

use App\Service\GoogleService;
use Google\Service\Calendar;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

class GoogleController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private JWTTokenManagerInterface $jwtManager,
        private GoogleService $googleService,
        private string $frontendUrl,
        ) {}

    #[Route('/api/auth/google', name: 'connect_google_start', methods: ['GET'])]
    public function connect(): RedirectResponse
    {
        // Build the redirect URL for Google OAuth but return it as JSON so the frontend can handle navigation
        return $this->clientRegistry
            ->getClient('google')
            ->redirect(
                ['openid', 'profile', 'email', 'https://www.googleapis.com/auth/calendar.readonly'], [
                    'access_type' => 'offline',
                    'prompt' => 'consent',
                ]);
    }

    #[Route('/api/auth/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function connectCheck(Request $request): RedirectResponse|JsonResponse
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');

        try {
            $provider = $client->getOAuth2Provider();

            $accessToken = $provider->getAccessToken(
                new \League\OAuth2\Client\Grant\AuthorizationCode(),
                [
                    'code' => $request->query->get('code'),
                ]
            );

            $googleUser = $provider->getResourceOwner($accessToken);

            $user = $this->googleService->saveUser($googleUser, $accessToken);    

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->redirect($this->frontendUrl . 'settings')
                ->setStatusCode(302);
        } catch (\Throwable $e) {
        return $this->json([
            'error' => 'Google OAuth failed: ' . $e->getMessage(),
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'previous_class' => $e->getPrevious()
                ? get_class($e->getPrevious())
                : null,
            'previous_message' => $e->getPrevious()?->getMessage(),
        ]);
    }
    }
        #[Route('/api/calendar/google', name: 'google_calendar_integration', methods: ['GET'])]
        public function googleCalendarIntegration(): JsonResponse
        {
            try {
              // Get the Google client
              $googleClient = $this->googleService->getGoogleClientForUser($this->getUser());

                $calendar = new Calendar($googleClient);

                $events = $calendar->events->listEvents('primary');

                return $this->json([
                    'message' => 'Google Calendar integration successful',
                    'events' => $events
                ]);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Google Calendar integration failed: ' . $e->getMessage()], 400);
            }
        }

        #[Route('/api/calendar/google/enabled', name: 'google_calendar_enabled_status', methods: ['GET'])]
        public function getGoogleCalendarEnabledStatus(): JsonResponse
        {
            /** @var User|null $user */
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'User not authenticated'], 401); 
            }

            $isEnabled = $this->googleService->isGoogleCalendarIntegrationEnabled($user);
            return $this->json(['enabled' => $isEnabled]);
        }
}
