<?php

namespace App\Controller;

use App\Entity\User;
use App\Application\Service\UserApplicationService;
use App\Service\ErrorHandlerService;
use App\DTO\UserRegistrationDTO;
use App\Infrastructure\Validation\RequestValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserApplicationService $userApplicationService,
        private RequestValidatorInterface $requestValidator,
        private ErrorHandlerService $errorHandler
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                throw new \RuntimeException('User not authenticated', JsonResponse::HTTP_UNAUTHORIZED);
            }
            return $this->json($this->userApplicationService->formatLoginResponse($user));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Login failed');
        }
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                throw new \RuntimeException('User not authenticated', JsonResponse::HTTP_UNAUTHORIZED);
            }
            return $this->json($this->userApplicationService->getUserProfile($user));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Authentication check failed');
        }
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $this->requestValidator->extractJsonData($request);
            
            $registrationDTO = new UserRegistrationDTO(
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['firstName'] ?? '',
                $data['lastName'] ?? '',
                $data['phone'] ?? null
            );

            $user = $this->userApplicationService->registerUser($registrationDTO);
            
            return $this->json(
                $this->userApplicationService->formatRegistrationResponse($user),
                JsonResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Registration failed');
        }
    }
}