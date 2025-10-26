<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Application\Service\UserApplicationService;
use App\DTO\UserRegistrationDTO;
use App\Exception\User\UserNotAuthenticatedException;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\Presenter\UserPresenter;
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
        private UserPresenter $userPresenter
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                throw new UserNotAuthenticatedException();
            }
            return $this->json($this->userPresenter->presentLoginResponse($this->userApplicationService->formatLoginResponse($user)));
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400;
            return $this->json(['error' => 'Login failed', 'message' => $e->getMessage()], $status);
        }
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                throw new UserNotAuthenticatedException();
            }
            return $this->json($this->userPresenter->presentProfile($this->userApplicationService->getUserProfile($user)));
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 401;
            return $this->json(['error' => 'Authentication check failed', 'message' => $e->getMessage()], $status);
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
                $this->userPresenter->presentRegistrationResponse($this->userApplicationService->formatRegistrationResponse($user)),
                JsonResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400;
            return $this->json(['error' => 'Registration failed', 'message' => $e->getMessage()], $status);
        }
    }
}