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

// Force a persistent sqlite DB for test to keep schema across multiple kernels
if (($_ENV['APP_ENV'] ?? 'test') === 'test') {
    $dbDir = dirname(__DIR__).'/var';
    if (!is_dir($dbDir)) { @mkdir($dbDir, 0777, true); }
    $dbPath = $dbDir.'/test.db';
    $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = 'sqlite:///'.$dbPath;
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
            // Seed test user for JWT auth
            try {
                $repo = $em->getRepository(\App\Entity\User::class);
                $user = $repo->findOneBy(['email' => 'test@example.com']);
                if (!$user) {
                    $user = (new \App\Entity\User())
                        ->setEmail('test@example.com')
                        ->setFirstName('Test')
                        ->setLastName('User')
                        ->setRoles(['ROLE_USER']);
                    // Hash password if service available
                    $password = 'password';
                    try {
                        $hasher = $container->get('security.user_password_hasher');
                        $user->setPassword($hasher->hashPassword($user, $password));
                    } catch (\Throwable $e) {
                        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
                    }
                    $em->persist($user);
                    $em->flush();
                }
            } catch (\Throwable $e) {
                // ignore seeding failures
            }
        }
        $kernel->shutdown();
    }
} catch (\Throwable $e) {
    // swallow on purpose; some suites don't need DB
}
