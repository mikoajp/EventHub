<?php

namespace App\Application\Service;

use App\DTO\UserRegistrationDTO;
use App\Entity\User;
use App\Exception\User\UserNotAuthenticatedException;
use App\Presenter\UserPresenter;
use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly UserApplicationService $userApplicationService,
        private readonly UserPresenter $userPresenter,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    /**
     * @param array<string,mixed> $credentials
     * @return array{payload: array<string,mixed>, refresh: string}
     */
    public function login(array $credentials): array
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';
        $user = $this->userRepository->findByEmail($email);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new UserNotAuthenticatedException('Invalid credentials');
        }
        
        // Generate JWT access token
        $accessToken = $this->jwtManager->create($user);
        
        // Generate refresh token
        $refreshEntity = $this->refreshTokenService->createToken($user);
        
        // Prepare response with token
        $loginData = $this->userApplicationService->formatLoginResponse($user);
        $loginData['token'] = $accessToken;
        
        $payload = $this->userPresenter->presentLoginResponse($loginData);
        
        return [
            'payload' => $payload + ['refresh_token' => $refreshEntity->getRefreshToken()],
            'refresh' => $refreshEntity->getRefreshToken(),
        ];
    }

    /**
     * @return array{payload: array<string,mixed>, refresh: string}
     */
    public function register(UserRegistrationDTO $dto): array
    {
        $user = $this->userApplicationService->registerUser($dto);
        
        // Generate JWT access token
        $accessToken = $this->jwtManager->create($user);
        
        // Generate refresh token
        $refreshEntity = $this->refreshTokenService->createToken($user);
        
        $registrationData = $this->userApplicationService->formatLoginResponse($user);
        $registrationData['token'] = $accessToken;
        
        $payload = $this->userPresenter->presentRegistrationResponse($registrationData);
        
        return [
            'payload' => $payload + ['refresh_token' => $refreshEntity->getRefreshToken()],
            'refresh' => $refreshEntity->getRefreshToken(),
        ];
    }

    /**
     * @return array{payload: array<string,mixed>, refresh: string}
     */
    public function refresh(string $refreshToken): array
    {
        if (!$refreshToken || strlen($refreshToken) !== 64) {
            throw new UserNotAuthenticatedException();
        }
        $rotated = $this->refreshTokenService->rotate($refreshToken);
        if (!$rotated) {
            throw new UserNotAuthenticatedException();
        }
        
        // Get user and generate new access token
        $user = $this->userRepository->findByEmail($rotated->getUsername());
        if (!$user) {
            throw new UserNotAuthenticatedException();
        }
        
        $accessToken = $this->jwtManager->create($user);
        
        return [
            'payload' => [
                'token' => $accessToken,
                'refresh_token' => $rotated->getRefreshToken(),
                'message' => 'Token refreshed'
            ],
            'refresh' => $rotated->getRefreshToken(),
        ];
    }

    public function logout(?string $refreshToken): array
    {
        if ($refreshToken) {
            $this->refreshTokenService->revoke($refreshToken);
        }
        return ['message' => 'Logged out'];
    }

    public function me(?User $user): array
    {
        if (!$user) {
            throw new UserNotAuthenticatedException();
        }
        return $this->userPresenter->presentProfile(
            $this->userApplicationService->getUserProfile($user)
        );
    }
}
