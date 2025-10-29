<?php

namespace App\Tests\Functional\Api;

use App\Entity\User;
use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Controller\Api\AuthController
 * @group functional
 * @group api
 * @group authentication
 */
final class AuthControllerTest extends BaseWebTestCase
{
    public function testRegisterCreatesNewUser(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123!',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'phone' => '+1234567890'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('id', $responseData['user']);
        $this->assertSame('newuser@example.com', $responseData['user']['email']);
        $this->assertSame('John', $responseData['user']['firstName']);
        $this->assertSame('Doe', $responseData['user']['lastName']);
        
        // Verify user was actually created in database
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'newuser@example.com']);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John', $user->getFirstName());
    }

    public function testRegisterRequiresEmail(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterRequiresPassword(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterRequiresFirstName(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
            'lastName' => 'User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterRequiresLastName(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'Test'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'not-an-email',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $client = static::createClient();
        
        // First registration
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'firstName' => 'First',
            'lastName' => 'User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // Second registration with same email
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'duplicate@example.com',
            'password' => 'password456',
            'firstName' => 'Second',
            'lastName' => 'User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterHashesPassword(): void
    {
        $client = static::createClient();
        
        $plainPassword = 'MySecurePassword123!';
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'hashtest@example.com',
            'password' => $plainPassword,
            'firstName' => 'Hash',
            'lastName' => 'Test'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // Verify password is hashed in database
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'hashtest@example.com']);
        
        $this->assertNotSame($plainPassword, $user->getPassword());
        $this->assertStringStartsWith('$', $user->getPassword()); // Bcrypt/Argon2 hash
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        
        // First register a user
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'logintest@example.com',
            'password' => 'password123',
            'firstName' => 'Login',
            'lastName' => 'Test'
        ]));
        
        // Then try to login
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'logintest@example.com',
            'password' => 'password123'
        ]));
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertNotEmpty($responseData['token']);
        $this->assertSame('logintest@example.com', $responseData['user']['email']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $client = static::createClient();
        
        // First register a user
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'wrongpass@example.com',
            'password' => 'correctpassword',
            'firstName' => 'Wrong',
            'lastName' => 'Pass'
        ]));
        
        // Try to login with wrong password
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'wrongpass@example.com',
            'password' => 'wrongpassword'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithNonexistentUser(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginRequiresEmail(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'password123'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginRequiresPassword(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMeEndpointReturnsCurrentUser(): void
    {
        $client = static::createClient();
        
        // Register and login
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'me@example.com',
            'password' => 'password123',
            'firstName' => 'Current',
            'lastName' => 'User'
        ]));
        
        $registrationResponse = json_decode($client->getResponse()->getContent(), true);
        $token = $registrationResponse['token'] ?? null;
        
        $this->assertNotNull($token, 'Registration should return a token');
        
        // Access /me endpoint with token
        $client->request('GET', '/api/auth/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('user', $responseData);
        $this->assertSame('me@example.com', $responseData['user']['email']);
        $this->assertSame('Current', $responseData['user']['firstName']);
        $this->assertSame('User', $responseData['user']['lastName']);
    }

    public function testMeEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/auth/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeEndpointRejectsInvalidToken(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/auth/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token-123',
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegisterReturnsJsonResponse(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'jsontest@example.com',
            'password' => 'password123',
            'firstName' => 'Json',
            'lastName' => 'Test'
        ]));
        
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $content = $client->getResponse()->getContent();
        $this->assertJson($content);
        
        $data = json_decode($content, true);
        $this->assertIsArray($data);
    }

    public function testLoginReturnsJsonResponse(): void
    {
        $client = static::createClient();
        
        // Register first
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'jsonlogin@example.com',
            'password' => 'password123',
            'firstName' => 'Json',
            'lastName' => 'Login'
        ]));
        
        // Then login
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'jsonlogin@example.com',
            'password' => 'password123'
        ]));
        
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $content = $client->getResponse()->getContent();
        $this->assertJson($content);
    }
}
