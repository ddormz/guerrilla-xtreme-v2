<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Blocked Device Fingerprint IDs
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of FingerprintJS visitorId hashes that should be
    | shadow-banned from tournament registration. These users will see a
    | fake success response but nothing will be saved to the database.
    |
    | Set via .env: BLOCKED_DEVICE_IDS=abc123,def456
    |
    */
    'blocked_ids' => array_filter(
        array_map('trim', explode(',', env('BLOCKED_DEVICE_IDS', '')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Admin Alert Email
    |--------------------------------------------------------------------------
    |
    | Email address to notify when a blocked device attempts registration.
    |
    */
    'admin_alert_email' => env('ABUSE_ALERT_EMAIL', 'danielorellanaramirez@gmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum registration attempts per IP within the decay window (minutes).
    |
    */
    'rate_limit_max' => (int) env('REGISTRATION_RATE_LIMIT', 5),
    'rate_limit_decay_minutes' => (int) env('REGISTRATION_RATE_DECAY', 10),
];
