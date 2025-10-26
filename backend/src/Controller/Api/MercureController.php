<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/mercure')]
class MercureController extends AbstractController
{
    /**
     * Get Mercure JWT token for subscribing to real-time updates
     */
    #[Route('/token', name: 'api_mercure_token', methods: ['GET'])]
    public function getToken(
        Request $request,
        Authorization $authorization,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        $topics = ['notifications'];

        if ($user) {
            // Add private user-specific topics
            $topics[] = sprintf('notifications/user/%s', $user->getId());
            $topics[] = 'events';
        }

        // Generate JWT cookie
        $cookie = $authorization->createCookie(
            request: $request,
            subscribe: $topics,
            publish: [] // Users can only subscribe, not publish
        );

        return $this->json([
            'token' => $cookie->getValue(),
            'cookie_name' => $cookie->getName(),
            'topics' => $topics,
            'mercure_url' => $this->getParameter('mercure.default_hub')
        ]);
    }

    /**
     * Health check for Mercure hub
     */
    #[Route('/health', name: 'api_mercure_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $mercureUrl = $this->getParameter('mercure.default_hub');

        if (!$mercureUrl) {
            return $this->json([
                'status' => 'error',
                'message' => 'Mercure URL not configured'
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'status' => 'ok',
            'mercure_url' => $mercureUrl,
        ]);
    }
}