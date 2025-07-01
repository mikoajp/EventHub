<?php

namespace App\Domain\User\Service;

use App\Entity\User;
use App\DTO\UserRegistrationDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function createUser(UserRegistrationDTO $registrationDTO): User
    {
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

    public function updateUserProfile(User $user, array $profileData): User
    {
        if (isset($profileData['firstName'])) {
            $user->setFirstName($profileData['firstName']);
        }
        
        if (isset($profileData['lastName'])) {
            $user->setLastName($profileData['lastName']);
        }
        
        if (isset($profileData['phone'])) {
            $user->setPhone($profileData['phone']);
        }

        $this->entityManager->flush();

        return $user;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->flush();
    }

    public function promoteToOrganizer(User $user): void
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_ORGANIZER', $roles)) {
            $roles[] = 'ROLE_ORGANIZER';
            $user->setRoles($roles);
            $this->entityManager->flush();
        }
    }

    public function demoteFromOrganizer(User $user): void
    {
        $roles = array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_ORGANIZER');
        $user->setRoles(array_values($roles));
        $this->entityManager->flush();
    }
}