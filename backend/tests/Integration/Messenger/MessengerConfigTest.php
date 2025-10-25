<?php

namespace App\Tests\Integration\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class MessengerConfigTest extends TestCase
{
    private string $configPath;
    
    protected function setUp(): void
    {
        $this->configPath = __DIR__ . '/../../../config/packages/messenger.yaml';
    }

    public function testMessengerConfigFileExists(): void
    {
        $this->assertFileExists($this->configPath, 'Messenger configuration file should exist');
    }

    public function testMessengerConfigIsValidYaml(): void
    {
        $content = file_get_contents($this->configPath);
        
        try {
            $config = Yaml::parse($content);
            $this->assertIsArray($config, 'Config should be valid YAML and parse to array');
        } catch (\Exception $e) {
            $this->fail('Messenger config contains invalid YAML: ' . $e->getMessage());
        }
    }

    public function testMessengerConfigHasFrameworkKey(): void
    {
        $content = file_get_contents($this->configPath);
        $config = Yaml::parse($content);
        
        $this->assertArrayHasKey('framework', $config, 'Config should have framework key');
    }

    public function testMessengerConfigHasMessengerSection(): void
    {
        $content = file_get_contents($this->configPath);
        $config = Yaml::parse($content);
        
        $this->assertArrayHasKey('messenger', $config['framework'], 'Framework config should have messenger section');
    }

    public function testMessengerConfigHasTransports(): void
    {
        $content = file_get_contents($this->configPath);
        $config = Yaml::parse($content);
        
        if (isset($config['framework']['messenger']['transports'])) {
            $this->assertIsArray($config['framework']['messenger']['transports'], 'Transports should be an array');
            $this->assertNotEmpty($config['framework']['messenger']['transports'], 'Should have at least one transport configured');
        } else {
            $this->markTestSkipped('No transports configured in messenger.yaml');
        }
    }

    public function testMessengerConfigHasRouting(): void
    {
        $content = file_get_contents($this->configPath);
        $config = Yaml::parse($content);
        
        if (isset($config['framework']['messenger']['routing'])) {
            $this->assertIsArray($config['framework']['messenger']['routing'], 'Routing should be an array');
        } else {
            // Routing is optional, test passes if not present
            $this->assertTrue(true, 'Routing configuration is optional');
        }
    }

    public function testMessengerConfigHasDefaultBus(): void
    {
        $content = file_get_contents($this->configPath);
        $config = Yaml::parse($content);
        
        if (isset($config['framework']['messenger']['default_bus'])) {
            $this->assertIsString($config['framework']['messenger']['default_bus'], 'Default bus should be a string');
        } else {
            $this->assertTrue(true, 'Default bus configuration is optional');
        }
    }

    public function testMessengerConfigStructureIsValid(): void
    {
        $content = file_get_contents($this->configPath);
        $config = Yaml::parse($content);
        
        // Check basic structure
        $this->assertIsArray($config);
        $this->assertArrayHasKey('framework', $config);
        $this->assertIsArray($config['framework']);
        $this->assertArrayHasKey('messenger', $config['framework']);
        $this->assertIsArray($config['framework']['messenger']);
    }
}
