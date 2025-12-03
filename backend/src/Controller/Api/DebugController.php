<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/api/debug/headers', name: 'api_debug_headers', methods: ['GET'])]
    public function headers(Request $request): JsonResponse
    {
        $headers = [];
        foreach ($request->headers->all() as $key => $value) {
            $headers[$key] = is_array($value) ? implode(', ', $value) : $value;
        }
        
        return new JsonResponse([
            'uri' => $request->getRequestUri(),
            'method' => $request->getMethod(),
            'headers' => $headers,
            'has_authorization' => $request->headers->has('Authorization'),
            'authorization_header' => $request->headers->get('Authorization'),
            'auth_user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : null,
        ]);
    }
}
