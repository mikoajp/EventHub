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

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Ensure DB schema exists once per process
        self::ensureSchema($this->entityManager);

        // Begin transaction for test isolation
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up
        if ($this->entityManager && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

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

    private static bool $schemaEnsured = false;

    public static function ensureSchema(\Doctrine\ORM\EntityManagerInterface $em): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $tool->updateSchema($metadata, true);
        }
        self::$schemaEnsured = true;
    }
}
