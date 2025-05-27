<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AuthService;
use App\Service\ErrorHandlerService;
use App\DTO\UserRegistrationDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private ErrorHandlerService $errorHandler
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            $this->authService->validateUser($user);
            return $this->json($this->authService->formatLoginResponse($user));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Login failed');
        }
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            $this->authService->validateUser($user);
            return $this->json($this->authService->formatUserProfileResponse($user));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Authentication check failed');
        }
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->registerUserFromRequest($request);
            return $this->json(
                $this->authService->formatRegistrationResponse($user),
                JsonResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Registration failed');
        }
    }
}