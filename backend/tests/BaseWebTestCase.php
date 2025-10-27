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

        // Begin transaction for test isolation
        // Note: Kernel client makes separate DB connections that can see uncommitted data
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up test data
        if ($this->entityManager && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        
        if ($this->entityManager) {
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
        $em = $container->get('doctrine')->getManager();
        
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
     * Commits data so it's visible to API requests in separate transactions
     */
    protected function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        
        // Commit so API can see the data (will be rolled back in tearDown)
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->commit();
            // Start new transaction for next operations
            $this->entityManager->beginTransaction();
        }
    }
}
