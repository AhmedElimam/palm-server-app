<?php

return [
    'tokens' => [
        'amazon' => env('APIFY_AMAZON_TOKEN', 'apify_api_zmVr5LMUhXGM0dhjBOGMBMCKSB6d1D3TnPvS'),
        'jumia' => env('APIFY_JUMIA_TOKEN', 'apify_api_zmVr5LMUhXGM0dhjBOGMBMCKSB6d1D3TnPvS'),
    ],

    'endpoints' => [
        'amazon_dataset' => 'https://api.apify.com/v2/datasets/OsBT6oq7cNrLDjF3Y/items',
        'jumia_actor_run' => 'https://api.apify.com/v2/actor-runs/Pouv6t7TAS5dkGhco',
    ],

    'limits' => [
        'default' => 10,
        'max' => 100,
    ],
]; 