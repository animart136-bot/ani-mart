<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rule-based deviation thresholds (percent above reference)
    |--------------------------------------------------------------------------
    | These are market-review bands, not legal price caps.
    */
    'thresholds' => [
        'normal_max' => 15.0,
        'price_review_max' => 30.0,
        'high_deviation_max' => 50.0,
    ],

    'assessments' => [
        'NORMAL' => 'NORMAL',
        'PRICE_REVIEW' => 'PRICE_REVIEW',
        'HIGH_DEVIATION' => 'HIGH_DEVIATION',
        'SIGNIFICANT_DEVIATION' => 'SIGNIFICANT_DEVIATION',
        'REFERENCE_UNAVAILABLE' => 'REFERENCE_UNAVAILABLE',
    ],

    'warning_assessments' => [
        'HIGH_DEVIATION',
        'SIGNIFICANT_DEVIATION',
    ],

    'notify_assessments' => [
        'SIGNIFICANT_DEVIATION',
    ],

    // Older values remain in history, but are not used as current guidance.
    'reference_current_days' => 7,
    'reference_max_age_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Admin vacation mode (disabled by default)
    |--------------------------------------------------------------------------
    | When enabled, only configured low-risk assessments may auto-approve.
    | High-risk products always go to pending_admin_review.
    | The system never auto-rejects because a price is above reference.
    */
    'vacation_mode' => [
        'enabled' => false,
        'auto_approve_low_risk' => true,
        'low_risk_assessments' => ['NORMAL'],
        'never_auto_reject' => true,
    ],

    'allowed_sources' => [
        'DA_DPI',
        'DA_REGIONAL',
        'PSA',
        'ADMIN_IMPORTED',
        'OTHER_OFFICIAL_SOURCE',
    ],

    'analyzer' => \App\Services\PriceAnalysis\RuleBasedPriceAnalyzer::class,
];
