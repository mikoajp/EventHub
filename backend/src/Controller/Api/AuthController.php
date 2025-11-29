<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Application\Service\UserApplicationService;
use App\DTO\UserRegistrationDTO;
use App\Exception\User\UserNotAuthenticatedException;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\Presenter\UserPresenter;
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
        private UserPresenter $userPresenter,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $this->userRepository->findByEmail($email);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new UserNotAuthenticatedException();
        }

        $refreshEntity = $this->container->get(\App\Service\RefreshTokenService::class)->createToken($user);
        $refreshToken = $refreshEntity->getRefreshToken();
        $responseData = $this->userPresenter->presentLoginResponse($this->userApplicationService->formatLoginResponse($user));
        $responseData['refresh_token'] = $refreshToken;
        $response = $this->json($responseData);
        $cookie = Cookie::create('refresh_token', $refreshToken, time() + 60*60*24*7, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            throw new UserNotAuthenticatedException();
        }
        return $this->json($this->userPresenter->presentProfile($this->userApplicationService->getUserProfile($user)));
    }

    #[Route('/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        // Extract refresh token from HttpOnly cookie
        $refreshToken = $request->cookies->get('refresh_token');
        if (!$refreshToken) {
            throw new UserNotAuthenticatedException();
        }
        // Placeholder validation - replace with persisted storage check
        if (strlen($refreshToken) !== 64) {
            throw new UserNotAuthenticatedException();
        }
        $service = $this->container->get(\App\Service\RefreshTokenService::class);
        $rotated = $service->rotate($refreshToken);
        if (!$rotated) {
            throw new UserNotAuthenticatedException();
        }
        $newRefresh = $rotated->getRefreshToken();
        $response = $this->json(['message' => 'Token refreshed', 'refresh_token' => $newRefresh]);
        $cookie = Cookie::create('refresh_token', $newRefresh, time() + 60*60*24*7, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $service = $this->container->get(\App\Service\RefreshTokenService::class);
        $token = $request->cookies->get('refresh_token');
        if ($token) { $service->revoke($token); }
        $response = $this->json(['message' => 'Logged out']);
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

        $user = $this->userApplicationService->registerUser($registrationDTO);
        
        $refreshEntity = $this->container->get(\App\Service\RefreshTokenService::class)->createToken($user);
        $refreshToken = $refreshEntity->getRefreshToken();
        $responseData = $this->userPresenter->presentRegistrationResponse($this->userApplicationService->formatRegistrationResponse($user));
        $responseData['refresh_token'] = $refreshToken;
        $response = $this->json($responseData, JsonResponse::HTTP_CREATED);
        $cookie = Cookie::create('refresh_token', $refreshToken, time() + 60*60*24*7, '/', null, false, true, false, 'Strict');
        $response->headers->setCookie($cookie);
        return $response;
    }
}