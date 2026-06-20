<?php

return [
    'scan_timeout' => (int) env('QSA_SCAN_TIMEOUT', 12),
    'scan_max_bytes' => (int) env('QSA_SCAN_MAX_BYTES', 2 * 1024 * 1024),
    'default_company_name' => env('QSA_DEFAULT_COMPANY_NAME', 'Quick SEO Analysis'),
];
