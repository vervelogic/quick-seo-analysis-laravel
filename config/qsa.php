<?php

return [
    'scan_timeout' => (int) env('QSA_SCAN_TIMEOUT', 20),
    'scan_connect_timeout' => (int) env('QSA_SCAN_CONNECT_TIMEOUT', 8),
    'scan_max_bytes' => (int) env('QSA_SCAN_MAX_BYTES', 2 * 1024 * 1024),
    'scan_user_agent' => env('QSA_SCAN_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36'),
    'scan_rate_limit_per_minute' => (int) env('QSA_SCAN_RATE_LIMIT_PER_MINUTE', 6),
    'lead_rate_limit_per_minute' => (int) env('QSA_LEAD_RATE_LIMIT_PER_MINUTE', 10),
    'default_company_name' => env('QSA_DEFAULT_COMPANY_NAME', 'Quick SEO Analysis'),
];
