<?php

return [

    'paths' => ['api/*'], /* Restricted to API only */

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => false, // false if using token auth (Bearer)
];
