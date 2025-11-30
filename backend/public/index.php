<?php

use App\Kernel;

// Custom error handler to suppress AMQP compatibility warnings
// This warning is triggered when both ext-amqp and php-amqp-compat are present
// The warning appears before headers are sent, causing HTTP 200 instead of proper status codes (like 401).
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Suppress only the specific AMQP compatibility warning
    if (str_contains($errstr, 'ext-amqp must be uninstalled to use php-amqp-compat')) {
        return true; // Suppress this warning
    }
    // Let other errors/warnings pass through to default handler
    return false;
});

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
