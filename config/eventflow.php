<?php

return [

    'tax_rate' => (float) env('EVENTFLOW_TAX_RATE', 0.10),

    'payments' => [
        'simulate_failure' => (bool) env('EVENTFLOW_SIMULATE_PAYMENT_FAILURE', false),
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
