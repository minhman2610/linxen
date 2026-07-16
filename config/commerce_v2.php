<?php

return [
    'enabled' => env('ERP_COMMERCE_V2_ENABLED', true),

    'base_url' => rtrim(
        (string) env(
            'ERP_COMMERCE_V2_BASE_URL',
            'https://3mg.ai/api/commerce/v2'
        ),
        '/'
    ),

    'site' => env('ERP_COMMERCE_V2_SITE', 'linxen'),

    'token' => env('ERP_COMMERCE_V2_TOKEN'),

    'timeout_seconds' => (int) env(
        'ERP_COMMERCE_V2_TIMEOUT',
        8
    ),

    'connect_timeout_seconds' => (int) env(
        'ERP_COMMERCE_V2_CONNECT_TIMEOUT',
        3
    ),

    'retry_times' => (int) env(
        'ERP_COMMERCE_V2_RETRY_TIMES',
        2
    ),

    'retry_sleep_ms' => (int) env(
        'ERP_COMMERCE_V2_RETRY_SLEEP_MS',
        250
    ),

    'cache_store' => env(
        'ERP_COMMERCE_V2_CACHE_STORE',
        'file'
    ),

    'fresh_cache_seconds' => (int) env(
        'ERP_COMMERCE_V2_FRESH_CACHE_SECONDS',
        10
    ),

    'stale_cache_seconds' => (int) env(
        'ERP_COMMERCE_V2_STALE_CACHE_SECONDS',
        300
    ),

    'stage_prefix' => env(
        'LINXEN_STOREFRONT_V2_PREFIX',
        'v2'
    ),

    'brand_name' => env(
        'LINXEN_STOREFRONT_V2_BRAND_NAME',
        'LIN XÉN'
    ),

    'support_phone' => env(
        'LINXEN_STOREFRONT_V2_SUPPORT_PHONE',
        ''
    ),

    'support_url' => env(
        'LINXEN_STOREFRONT_V2_SUPPORT_URL',
        ''
    ),
];
