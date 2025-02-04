<?php

return [
    // The Telegram BOT api token
    'token' => env('TELEGRAM_TOKEN'),
    'vendor_token' => env('TELEGRAM_VENDOR_TOKEN'),
    'driver_token' => env('TELEGRAM_DRIVER_TOKEN'),

    // if the webhook mode must validate the incoming IP range is from a telegram server
    'safe_mode' => env('APP_ENV', 'local') === 'production',

    // Extra or specific configurations
    'config' => [],

    // Set if the service provider should automatically load
    // handlers from /routes/telegram.php
    'routes' => env('TELEGRAM_ROUTES', false),

    // Enable or disable Nutgram mixins
    'mixins' => true,

    // Path to save files generated by nutgram:make command
    'namespace' => app_path('Telegram'),

    // Set log channel
    'log_channel' => env('TELEGRAM_LOG_CHANNEL', 'null'),
];
