<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Application\Service\UserApplicationService;
use App\DTO\UserRegistrationDTO;
use App\Exception\User\UserNotAuthenticatedException;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\Application\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    private const REFRESH_COOKIE_LIFETIME = 604800; // 7 days

    public function __construct(
        private RequestValidatorInterface $requestValidator,
        private AuthService $authService
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $result = $this->authService->login($data);
        $response = $this->json($result['payload']);
        $response->headers->setCookie($this->createRefreshTokenCookie($result['refresh']));
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
        if (!$refreshToken) {
            return $this->json(['message' => 'Missing refresh token'], 401);
        }
        $result = $this->authService->refresh((string)$refreshToken);
        $response = $this->json($result['payload']);
        $response->headers->setCookie($this->createRefreshTokenCookie($result['refresh']));
        return $response;
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->cookies->get('refresh_token');
        $payload = $token ? $this->authService->logout($token) : ['message' => 'Logged out'];
        $response = $this->json($payload);
        $response->headers->clearCookie('refresh_token', '/', null, true, true, 'Strict');
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
            $data['phone'] ?? null,
            $data['wantToBeOrganizer'] ?? false
        );
        $result = $this->authService->register($registrationDTO);
        $response = $this->json($result['payload'], JsonResponse::HTTP_CREATED);
        $response->headers->setCookie($this->createRefreshTokenCookie($result['refresh']));
        return $response;
    }

    private function createRefreshTokenCookie(string $token): Cookie
    {
        // Use secure flag only in production (HTTPS)
        $isSecure = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';
        
        // Use Lax instead of Strict for cross-origin in development
        // Strict can block cookies between localhost:5173 and localhost:8001
        $sameSite = $isSecure ? Cookie::SAMESITE_STRICT : Cookie::SAMESITE_LAX;
        
        return Cookie::create(
            'refresh_token',
            $token,
            time() + self::REFRESH_COOKIE_LIFETIME,
            '/',
            null,
            $isSecure, // secure only on HTTPS
            true,      // httpOnly
            false,     // raw
            $sameSite  // Lax in dev, Strict in prod
        );
    }
}