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
