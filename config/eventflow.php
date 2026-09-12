<?php

return [

    'tax_rate' => (float) env('EVENTFLOW_TAX_RATE', 0.10),

    'demo' => [
        'customer_name' => env('EVENTFLOW_DEMO_CUSTOMER_NAME', 'Demo Customer'),
        'customer_email' => env('EVENTFLOW_DEMO_CUSTOMER_EMAIL', 'customer@eventflow.test'),
        'customer_phone' => env('EVENTFLOW_DEMO_CUSTOMER_PHONE', '9999999999'),
    ],

    'payments' => [
        'driver' => env('EVENTFLOW_PAYMENT_DRIVER', 'fake'),
        'simulate_failure' => (bool) env('EVENTFLOW_SIMULATE_PAYMENT_FAILURE', false),
        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'currency' => env('RAZORPAY_CURRENCY', 'INR'),
        ],
    ],

    'notifications' => [
        'mail_enabled' => (bool) env('EVENTFLOW_MAIL_NOTIFICATIONS', true),
    ],

    'rate_limits' => [
        'login' => [
            'max_attempts' => (int) env('EVENTFLOW_LOGIN_RATE_LIMIT', 5),
            'decay_minutes' => 1,
        ],
        'api' => [
            'max_attempts' => (int) env('EVENTFLOW_API_RATE_LIMIT', 60),
            'decay_minutes' => 1,
        ],
    ],

];
