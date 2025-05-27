<?php

namespace App\Service;

use App\Entity\User;
use App\DTO\UserRegistrationDTO;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    private const CACHE_TTL_USER_PROFILE = 3600; // 1 hour
    private const CACHE_KEY_USER_PROFILE_PREFIX = 'user.profile.';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private CacheService $cacheService,
        private UserRepository $userRepository
    ) {}

    public function validateUser(?User $user): void
    {
        if (!$user) {
            throw new \RuntimeException('User not authenticated', JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    public function registerUserFromRequest(Request $request): User
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new \InvalidArgumentException('Invalid JSON', JsonResponse::HTTP_BAD_REQUEST);
        }

        $registrationDTO = new UserRegistrationDTO(
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['phone'] ?? null
        );

        $errors = $this->validator->validate($registrationDTO);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException(
                json_encode(['errors' => $errorMessages]),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $user = new User();
        $user->setEmail($registrationDTO->email)
            ->setFirstName($registrationDTO->firstName)
            ->setLastName($registrationDTO->lastName)
            ->setPhone($registrationDTO->phone)
            ->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $registrationDTO->password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function formatLoginResponse(User $user): array
    {
        $this->invalidateUserProfileCache($user);
        
        return [
            'user' => $this->formatUserData($user),
            'message' => 'Login successful'
        ];
    }

    public function formatUserProfileResponse(User $user): array
    {
        $cacheKey = self::CACHE_KEY_USER_PROFILE_PREFIX . $user->getId();
        
        return $this->cacheService->get($cacheKey, function() use ($user) {
            $data = $this->formatUserData($user);
            $data['createdAt'] = $user->getCreatedAt()->format('c');
            return $data;
        }, self::CACHE_TTL_USER_PROFILE);
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
        ];
    }

    /**
     * Invalidate user profile cache
     */
    private function invalidateUserProfileCache(User $user): void
    {
        $this->cacheService->delete(self::CACHE_KEY_USER_PROFILE_PREFIX . $user->getId());
        $this->cacheService->delete('user.email.' . md5($user->getEmail()));
    }
}