<?php
return [
    'paths' => ['api/*', 'saas/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://centralerp.planettecglobal.com',
        'https://www.centralerp.planettecglobal.com',
        'https://testpoly.centralerp.planettecglobal.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
