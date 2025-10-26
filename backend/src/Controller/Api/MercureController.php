<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

#[Route('/api/mercure')]
class MercureController extends AbstractController
{
    /**
     * Get Mercure JWT token for subscribing to real-time updates
     */
    #[Route('/token', name: 'api_mercure_token', methods: ['GET'])]
    public function getToken(
        Authorization $authorization,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            // Topics the user can subscribe to
            $topics = ['notifications']; // Public notifications
            
            if ($user) {
                // Add private user-specific topics
                $topics[] = "notifications/user/{$user->getId()->toString()}";
                $topics[] = 'events'; // Authenticated users can see event updates
            }

            // Generate JWT token for Mercure subscription
            $token = $authorization->createCookie(
                subscribes: $topics,
                publishes: [] // Users cannot publish, only subscribe
            );

            return $this->json([
                'token' => $token->getValue(),
                'topics' => $topics,
                'mercure_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate Mercure token',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Health check for Mercure hub
     */
    #[Route('/health', name: 'api_mercure_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        try {
            $mercureUrl = $_ENV['MERCURE_URL'] ?? null;
            
            if (!$mercureUrl) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Mercure URL not configured'
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            return $this->json([
                'status' => 'ok',
                'mercure_url' => $mercureUrl,
                'public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? null
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
