<?php

return [
    'name' => env('HOTEL_NAME', env('APP_NAME', 'Odara Food Pvt Limited')),
    'vat_rate' => (float) env('HOTEL_VAT_RATE', 18.00),
    'service_charge_rate' => (float) env('HOTEL_SERVICE_CHARGE_RATE', 10.00),
    'max_cashier_discount_percentage' => (float) env('HOTEL_MAX_CASHIER_DISCOUNT', 10.00),
    'table_session_cookie' => env('HOTEL_TABLE_COOKIE_NAME', 'hotel_table_session_token'),
    'table_session_ttl_minutes' => (int) env('HOTEL_TABLE_SESSION_TTL', 360),
    'table_session_same_site' => env('HOTEL_TABLE_COOKIE_SAME_SITE', 'lax'),
    'lan_url' => env('HOTEL_LAN_URL', 'http://192.168.1.100:8000'),
    'sms' => [
        'enabled' => (bool) env('HOTEL_SMS_ENABLED', true),
        'endpoint' => env('HOTEL_SMS_ENDPOINT', 'https://api.sms-gateway.local/v1/send'),
        'api_key' => env('HOTEL_SMS_API_KEY', 'local-dev-sms-key'),
        'sender_id' => env('HOTEL_SMS_SENDER_ID', 'ODARAFOOD'),
        'rate_limit_per_minute' => (int) env('HOTEL_SMS_RATE_LIMIT', 5),
    ],
];
