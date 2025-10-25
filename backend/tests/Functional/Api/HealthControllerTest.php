<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpoint(): void
    {
         = static::createClient();
        ->request('GET', '/health');
        ->assertResponseIsSuccessful();
        ->assertJson(->getResponse()->getContent());
        ->assertSame(['status' => 'ok'], json_decode(->getResponse()->getContent(), true));
    }
}
