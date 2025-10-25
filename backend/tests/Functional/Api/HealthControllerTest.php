<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpoint(): void
    {
        self::bootKernel();
        $client = static::createClient();
        $client->request('GET', '/health');
        $this->assertResponseIsSuccessful();
        $this->assertSame(['status' => 'ok'], json_decode($client->getResponse()->getContent(), true));
    }
}
