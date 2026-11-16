<?php

return [
    'name' => 'ChikondiCare',
    'tagline' => 'Community Health Centre',
    'address' => env('CLINIC_ADDRESS', 'Lilongwe, Malawi'),
    'phone' => env('CLINIC_PHONE', '+265 000 000 000'),
    'currency' => 'MWK',
    'timezone' => env('APP_TIMEZONE', 'Africa/Blantyre'),

    // Inactivity timeout (minutes). Keep SESSION_LIFETIME in .env >= this value.
    'session_timeout' => (int) env('CHIKONDI_SESSION_TIMEOUT', 30),

    // Login lockout
    'max_login_attempts' => (int) env('CHIKONDI_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('CHIKONDI_LOCKOUT_MINUTES', 15),

    'per_page_options' => [10, 25, 50, 100],
    'default_per_page' => 10,
    'default_slot_minutes' => 30,
];
