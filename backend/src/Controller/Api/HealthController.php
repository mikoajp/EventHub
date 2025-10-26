<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok'], 200);
    }

    #[Route('/api/health', name: 'api_health_check', methods: ['GET'])]
    public function apiHealth(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok'], 200);
    }
}