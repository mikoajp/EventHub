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
        try {
            $container = static::getContainer();
            $em = $container->get('doctrine')->getManager();
            $repo = $em->getRepository(\App\Entity\User::class);
            $user = $repo->findOneBy(['email' => $email]);
            if (!$user) {
                $user = (new \App\Entity\User())
                    ->setEmail($email)
                    ->setFirstName('Test')
                    ->setLastName('User')
                    ->setRoles($roles ?: ['ROLE_USER'])
                    ->setPassword(password_hash('password', PASSWORD_BCRYPT));
                $em->persist($user);
                $em->flush();
            }
            if ($container->has('lexik_jwt_authentication.jwt_manager')) {
                $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');
                return $jwtManager->create($user);
            }
        } catch (\Throwable $e) {
            // fall through to simple token
        }
        return base64_encode(json_encode([
            'email' => $email,
            'roles' => $roles ?: ['ROLE_USER'],
            'exp' => time() + 3600
        ]));
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
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        
        $content = $response->getContent();
        $this->assertJson($content);
        
        return json_decode($content, true);
    }

    /**
     * Persist entity for test
     */
    protected function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
