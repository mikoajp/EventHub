<?php

namespace App\Application\Service;

use App\Domain\User\Service\UserDomainService;
use App\Entity\User;
use App\DTO\UserRegistrationDTO;
use App\Exception\Validation\ValidationException;
use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class UserApplicationService
{
    private const CACHE_TTL_USER_PROFILE = 3600; // 1 hour
    private const CACHE_KEY_USER_PROFILE_PREFIX = 'user.profile.';

    public function __construct(
        private UserDomainService $userDomainService,
        private ValidatorInterface $validator,
        private CacheInterface $cache
    ) {}

    public function registerUser(UserRegistrationDTO $registrationDTO): User
    {
        $errors = $this->validator->validate($registrationDTO);
        if (count($errors) > 0) {
            $violations = [];
            foreach ($errors as $error) {
                $violations[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new ValidationException($violations);
        }

        try {
            $user = $this->userDomainService->createUser($registrationDTO);
        } catch (UniqueConstraintViolationException $e) {
            // Handle duplicate email error
            throw new ValidationException([
                'email' => 'An account with this email already exists.'
            ]);
        }
        
        // Invalidate cache
        $this->invalidateUserCache($user);
        
        return $user;
    }

    public function updateUserProfile(User $user, array $profileData): User
    {
        $updatedUser = $this->userDomainService->updateUserProfile($user, $profileData);
        
        // Invalidate cache
        $this->invalidateUserCache($user);
        
        return $updatedUser;
    }

    public function getUserProfile(User $user): array
    {
        $cacheKey = self::CACHE_KEY_USER_PROFILE_PREFIX . $user->getId();
        
        return $this->cache->get($cacheKey, function() use ($user) {
            return $this->formatUserData($user);
        }, self::CACHE_TTL_USER_PROFILE);
    }

    public function formatLoginResponse(User $user): array
    {
        $this->invalidateUserCache($user);
        
        return [
            'user' => $this->formatUserData($user),
            'message' => 'Login successful'
        ];
    }

    public function formatRegistrationResponse(User $user): array
    {
        return [
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->getId()->toString(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
            ]
        ];
    }

    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('c'),
        ];
    }

    private function invalidateUserCache(User $user): void
    {
        $this->cache->delete(self::CACHE_KEY_USER_PROFILE_PREFIX . $user->getId());
        $this->cache->delete('user.email.' . md5($user->getEmail()));
    }
}