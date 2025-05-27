<?php

namespace App\Service;

use App\Entity\User;
use App\DTO\UserRegistrationDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
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
        return [
            'user' => $this->formatUserData($user),
            'message' => 'Login successful'
        ];
    }

    public function formatUserProfileResponse(User $user): array
    {
        $data = $this->formatUserData($user);
        $data['createdAt'] = $user->getCreatedAt()->format('c');
        return $data;
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
}