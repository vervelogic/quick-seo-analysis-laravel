<?php

return [
    'expected_count' => 13,

    // Primary deterministic source for the one-time legacy blog migration.
    // On the VPS, place the full 13 verified URLs here, one per line.
    'urls_file' => storage_path('app/legacy-import/known_blog_urls.txt'),

    // Fallback URLs visible in the current workspace. Replace or extend via urls_file.
    'urls' => [
        'https://www.quickseoanalysis.com/blog/which-techniques-provide-greater-value-to-seo',
        'https://www.quickseoanalysis.com/blog/check-out-an-ultimate-guide-for-seo-audit-to-boost-your-seo-ranking',
        'https://www.quickseoanalysis.com/blog/see-how-these-tips-could-help-you-to-boost-the-conversion-rate-of-your-site',
        'https://www.quickseoanalysis.com/blog/you-ll-be-amazed-to-know-the-reasons-why-your-website-needs-an-seo-audits',
    ],
];
