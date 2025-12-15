<?php

return [
    'api_base_url' => env('BAKKU_API_BASE_URL', 'https://api.bakku.cloud/v1/'),
    'site_id' => env('BAKKU_SITE_ID', ''),
    'api_token' => env('BAKKU_SITE_API_TOKEN', ''),
    'cache_ttl' => env('BAKKU_CACHE_TTL', 30),
];
