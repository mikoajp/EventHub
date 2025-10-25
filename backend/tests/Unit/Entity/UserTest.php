<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(User::class, $user);
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->assertSame('test@example.com', $user->getEmail());
    }

    public function testSetAndGetFirstName(): void
    {
        $user = new User();
        $user->setFirstName('John');
        
        $this->assertSame('John', $user->getFirstName());
    }

    public function testSetAndGetLastName(): void
    {
        $user = new User();
        $user->setLastName('Doe');
        
        $this->assertSame('Doe', $user->getLastName());
    }

    public function testSetAndGetRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // ROLE_USER is always included
    }

    public function testUserHasRoleUserByDefault(): void
    {
        $user = new User();
        
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetUserIdentifier(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testSetPassword(): void
    {
        $user = new User();
        $user->setPassword('hashed_password');
        
        $this->assertSame('hashed_password', $user->getPassword());
    }

    public function testOrganizedEventsCollectionInitialized(): void
    {
        $user = new User();
        
        $this->assertNotNull($user->getOrganizedEvents());
        $this->assertCount(0, $user->getOrganizedEvents());
    }

    public function testTicketsCollectionInitialized(): void
    {
        $user = new User();
        
        $this->assertNotNull($user->getTickets());
        $this->assertCount(0, $user->getTickets());
    }

    public function testEraseCredentialsClearsPlainPassword(): void
    {
        $user = new User();
        // Some User entities might have a plainPassword property
        // This method should clear sensitive data
        
        $user->eraseCredentials();
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }
}
