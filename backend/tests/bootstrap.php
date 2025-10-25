<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
     = dirname(__DIR__).'/.env';\n    if (file_exists()) { (new Dotenv())->bootEnv(); } else { \\['APP_ENV'] = \\['APP_ENV'] = \\['APP_ENV'] ?? 'test'; }
}

