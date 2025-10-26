<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Keep tests independent from CI .env; default to test when missing
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? '0';
$_SERVER['KERNEL_CLASS'] = $_ENV['KERNEL_CLASS'] = \App\Kernel::class;

if (class_exists(Dotenv::class)) {
    $envPath = dirname(__DIR__).'/.env.test';
    if (file_exists($envPath)) {
        (new Dotenv())->bootEnv($envPath);
    }
}


// Auto initialize test database schema to avoid "no such table" errors
try {
    if (($_ENV['APP_ENV'] ?? 'test') === 'test') {
        $kernel = new \App\Kernel('test', false);
        $kernel->boot();
        $container = $kernel->getContainer();
        if ($container->has('doctrine')) {
            $em = $container->get('doctrine')->getManager();
            $metadata = $em->getMetadataFactory()->getAllMetadata();
            if (!empty($metadata)) {
                // Create or update schema for all managers
                $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
                $tool->dropDatabase();
                $tool->createSchema($metadata);
            }
        }
        $kernel->shutdown();
    }
} catch (\Throwable $e) {
    // swallow on purpose; some suites don't need DB
}
