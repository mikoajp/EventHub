<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base test case for functional/API tests
 * Provides authenticated client and helper methods
 */
abstract class BaseWebTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    protected ?EntityManagerInterface $entityManager = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        // Ensure DB schema exists once per process
        BaseTestCase::ensureSchema($this->entityManager);

        // DO NOT use transactions in functional tests
        // Kernel client uses separate DB connections that need committed data
        // We'll clean up data in tearDown by truncating tables
        
        // Clear cache at the start of each test to ensure fresh data
        $this->clearCache();
    }

    protected function tearDown(): void
    {
        // Clean up test data by truncating tables
        // DO NOT use rollback - API needs committed data during test
        if ($this->entityManager) {
            try {
                $connection = $this->entityManager->getConnection();
                $platform = $connection->getDatabasePlatform();
                
                // Different cleanup strategy for SQLite vs PostgreSQL
                if ($platform->getName() === 'sqlite') {
                    // For SQLite, disable foreign keys and delete all rows
                    $connection->executeStatement('PRAGMA foreign_keys = OFF');
                    
                    // Delete in correct order (children first)
                    $tables = ['ticket', 'order_item', 'ticket_type', '"order"', 'event', 'idempotency_key', 'user', 'refresh_token'];
                    foreach ($tables as $table) {
                        try {
                            $connection->executeStatement("DELETE FROM {$table}");
                        } catch (\Exception $e) {
                            // Table might not exist yet
                        }
                    }
                    
                    $connection->executeStatement('PRAGMA foreign_keys = ON');
                } else {
                    // For PostgreSQL, use TRUNCATE with CASCADE
                    $connection->executeStatement('SET CONSTRAINTS ALL DEFERRED');
                    
                    $tables = ['ticket', 'ticket_type', 'order_item', '"order"', 'event', 'user', 'idempotency_key', 'refresh_token'];
                    foreach ($tables as $table) {
                        $connection->executeStatement($platform->getTruncateTableSQL($table, true));
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
            
            $this->entityManager->close();
            $this->entityManager = null;
        }

        $this->client = null;
        parent::tearDown();
    }

    /**
     * Create authenticated client with JWT token
     */
    protected function createAuthenticatedClient(string $email = 'test@example.com', array $roles = ['ROLE_USER']): KernelBrowser
    {
        $token = $this->generateJwtToken($email, $roles);
        
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
        
        return $this->client;
    }

    /**
     * Generate JWT token for testing
     */
    protected function generateJwtToken(string $email, array $roles = []): string
    {
        $container = static::getContainer();
        
        // Use the test's EntityManager to ensure visibility
        $em = $this->entityManager ?? $container->get('doctrine')->getManager();
        
        // Ensure schema exists for functional tests
        BaseTestCase::ensureSchema($em);
        
        $repo = $em->getRepository(\App\Entity\User::class);
        $user = $repo->findOneBy(['email' => $email]);
        
        if (!$user) {
            // Create user with properly hashed password
            $passwordHasher = $container->get('security.password_hasher');
            $user = new \App\Entity\User();
            $user->setEmail($email)
                ->setFirstName('Test')
                ->setLastName('User')
                ->setRoles($roles ?: ['ROLE_USER']);
            
            // Use Symfony password hasher
            $hashedPassword = $passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            
            $em->persist($user);
            $em->flush();
            
            // Commit transaction so API can see the user
            if ($em->getConnection()->isTransactionActive()) {
                $em->commit();
                $em->beginTransaction();
            }
        }
        
        // Always try to use real JWT manager
        if (!$container->has('lexik_jwt_authentication.jwt_manager')) {
            throw new \RuntimeException('JWT Manager not available in test environment');
        }
        
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');
        return $jwtManager->create($user);
    }

    /**
     * Make JSON API request
     */
    protected function jsonRequest(string $method, string $uri, array $data = [], array $headers = []): Response
    {
        $defaultHeaders = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            array_merge($defaultHeaders, $headers),
            json_encode($data)
        );

        return $this->client->getResponse();
    }

    /**
     * Assert JSON response
     */
    protected function assertJsonResponse(Response $response, int $statusCode = 200): array
    {
        $this->assertSame($statusCode, $response->getStatusCode());
        
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType, 'Response should have Content-Type header');
        $this->assertStringContainsString('application/json', $contentType, 'Response Content-Type should be application/json');
        
        $content = $response->getContent();
        $this->assertJson($content);
        
        return json_decode($content, true);
    }

    /**
     * Persist entity for test
     * Data is immediately visible to API since we don't use transactions in functional tests
     */
    protected function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        
        // For file-based SQLite, ensure transaction is committed so other connections can see data
        $connection = $this->entityManager->getConnection();
        if ($connection->getDatabasePlatform()->getName() === 'sqlite') {
            if ($connection->isTransactionActive()) {
                $connection->commit();
                $connection->beginTransaction();
            }
        }
        
        // Clear cache after persisting to ensure API sees fresh data
        $this->clearCache();
    }
    
    /**
     * Clear cache to ensure fresh data
     */
    protected function clearCache(): void
    {
        try {
            $cache = static::getContainer()->get('App\\Infrastructure\\Cache\\CacheInterface');
            if ($cache && $cache->isEnabled()) {
                $cache->clear();
            }
        } catch (\Exception $e) {
            // Ignore cache errors in tests
        }
    }
}
