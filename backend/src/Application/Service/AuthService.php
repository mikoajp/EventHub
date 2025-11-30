<?php

namespace App\Application\Service;

use App\DTO\UserRegistrationDTO;
use App\Entity\User;
use App\Exception\User\UserNotAuthenticatedException;
use App\Presenter\UserPresenter;
use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly UserApplicationService $userApplicationService,
        private readonly UserPresenter $userPresenter,
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
            throw new UserNotAuthenticatedException();
        }
        $refreshEntity = $this->refreshTokenService->createToken($user);
        $payload = $this->userPresenter->presentLoginResponse(
            $this->userApplicationService->formatLoginResponse($user)
        );
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
        $refreshEntity = $this->refreshTokenService->createToken($user);
        $payload = $this->userPresenter->presentRegistrationResponse(
            $this->userApplicationService->formatRegistrationResponse($user)
        );
        $payload['refresh_token'] = $refreshEntity->getRefreshToken();
        return [
            'payload' => $payload,
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
        return [
            'payload' => ['message' => 'Token refreshed', 'refresh_token' => $rotated->getRefreshToken()],
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
