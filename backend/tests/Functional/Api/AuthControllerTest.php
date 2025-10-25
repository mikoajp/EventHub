<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends WebTestCase
{
    public function testRegisterEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Test User'
        ]));
        
        // Should not be 404
        $this->assertNotSame(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testLoginEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));
        
        // Should not be 404
        $this->assertNotSame(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testRegisterRequiresEmail(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'password123',
            'name' => 'Test User'
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
            'name' => 'Test User'
        ]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginRequiresCredentials(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
