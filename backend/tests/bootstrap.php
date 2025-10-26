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

// Initialize DB schema for tests if Doctrine is available
try {
    if (($_ENV['APP_ENV'] ?? 'test') === 'test') {
        $kernel = new \App\Kernel('test', false);
        $kernel->boot();
        $container = $kernel->getContainer();
        if ($container->has('doctrine')) {
            $doctrine = $container->get('doctrine');
            foreach ($doctrine->getManagers() as $em) {
                $metadata = $em->getMetadataFactory()->getAllMetadata();
                if (!empty($metadata)) {
                    $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
                    $tool->dropDatabase();
                    $tool->createSchema($metadata);
                }
            }
        }
        $kernel->shutdown();
    }
} catch (\Throwable $e) {
    // ignore
}

