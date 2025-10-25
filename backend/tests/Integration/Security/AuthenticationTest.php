<?php

namespace App\Tests\Integration\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuthenticationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testSecurityConfigurationIsLoaded(): void
    {
        $container = static::getContainer();
        
        $this->assertTrue(
            $container->has('security.authenticator.manager') ||
            $container->has('security.token_storage')
        );
    }

    public function testJwtAuthenticatorIsRegistered(): void
    {
        $container = static::getContainer();
        
        $this->assertTrue(
            $container->has('App\Security\JwtAuthenticator') ||
            $container->has('lexik_jwt_authentication.jwt_manager')
        );
    }

    public function testPasswordHasherIsAvailable(): void
    {
        $container = static::getContainer();
        
        $this->assertTrue(
            $container->has('security.password_hasher') ||
            $container->has('security.user_password_hasher')
        );
    }
}
