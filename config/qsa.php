<?php

return [
    'scan_timeout' => (int) env('QSA_SCAN_TIMEOUT', 20),
    'scan_connect_timeout' => (int) env('QSA_SCAN_CONNECT_TIMEOUT', 8),
    'scan_max_bytes' => (int) env('QSA_SCAN_MAX_BYTES', 2 * 1024 * 1024),
    'scan_user_agent' => env('QSA_SCAN_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36'),
    'scan_rate_limit_per_minute' => (int) env('QSA_SCAN_RATE_LIMIT_PER_MINUTE', 6),
    'authenticated_scan_rate_limit_per_minute' => (int) env('QSA_AUTH_SCAN_RATE_LIMIT_PER_MINUTE', 20),
    'lead_rate_limit_per_minute' => (int) env('QSA_LEAD_RATE_LIMIT_PER_MINUTE', 10),
    'default_company_name' => env('QSA_DEFAULT_COMPANY_NAME', 'Quick SEO Analysis'),
    'public_scan_abuse' => [
        'daily_anonymous_quota' => (int) env('QSA_DAILY_ANONYMOUS_SCAN_QUOTA', 5),
        'duplicate_scan_cooldown_minutes' => (int) env('QSA_DUPLICATE_SCAN_COOLDOWN_MINUTES', 30),
        'verification_trigger_after_abuse_events' => (int) env('QSA_VERIFICATION_TRIGGER_AFTER_ABUSE_EVENTS', 3),
        'verification_lock_hours' => (int) env('QSA_VERIFICATION_LOCK_HOURS', 12),
        'messages' => [
            'daily_quota' => env('QSA_SCAN_DAILY_QUOTA_MESSAGE', 'You have reached the free daily scan limit for this connection. Please sign in to continue.'),
            'duplicate_cooldown' => env('QSA_SCAN_DUPLICATE_MESSAGE', 'This URL was already scanned recently from this connection. Please wait before scanning it again.'),
            'verification_required' => env('QSA_SCAN_VERIFICATION_MESSAGE', 'Too many anonymous scan attempts were detected. Please sign in with Google or use your client login to continue.'),
        ],
    ],
];
