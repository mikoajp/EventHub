<?php

namespace App\Tests\Integration\Repository;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(UserRepository::class);
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testFindByEmailMethod(): void
    {
        // Test that the method exists and can be called
        $result = $this->repository->findOneBy(['email' => 'nonexistent@example.com']);
        
        $this->assertNull($result);
    }

    public function testCountUsers(): void
    {
        $count = $this->repository->count([]);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
