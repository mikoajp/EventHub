<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base test case for integration tests
 * Provides database setup and cleanup
 */
abstract class BaseTestCase extends KernelTestCase
{
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Begin transaction for test isolation
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up
        if ($this->entityManager && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        $this->entityManager->close();
        $this->entityManager = null;

        parent::tearDown();
    }

    /**
     * Persist and flush entity
     */
    protected function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Clear entity manager
     */
    protected function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }
}
