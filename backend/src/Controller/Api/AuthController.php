<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Application\Service\UserApplicationService;
use App\DTO\UserRegistrationDTO;
use App\Exception\User\UserNotAuthenticatedException;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\Presenter\UserPresenter;
use App\Application\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;\nuse Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;\nuse App\Repository\UserRepository;\nuse Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserApplicationService $userApplicationService,
        private RequestValidatorInterface $requestValidator,
        private AuthService $authService
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $result = $this->authService->login($data);
        $response = $this->json($result['payload']);
        $cookie = Cookie::create('refresh_token', $result['refresh'], time() + 60*60*24*7, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json($this->authService->me($user));
    }

    #[Route('/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        // Extract refresh token from HttpOnly cookie
        $refreshToken = $request->cookies->get('refresh_token');
        $result = $this->authService->refresh((string)$refreshToken);
        $response = $this->json($result['payload']);
        $cookie = Cookie::create('refresh_token', $result['refresh'], time() + 60*60*24*7, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->cookies->get('refresh_token');
        $payload = $this->authService->logout($token);
        $response = $this->json($payload);
        $cookie = Cookie::create('refresh_token', '', time() - 3600, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->requestValidator->extractJsonData($request);
        $registrationDTO = new UserRegistrationDTO(
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['phone'] ?? null
        );
        $result = $this->authService->register($registrationDTO);
        $response = $this->json($result['payload'], JsonResponse::HTTP_CREATED);
        $cookie = Cookie::create('refresh_token', $result['refresh'], time() + 60*60*24*7, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }
}