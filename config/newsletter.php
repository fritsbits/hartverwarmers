<?php

return [
    /*
     * One-off dormant-contact reactivation campaign.
     */
    'reactivation_active' => env('NEWSLETTER_REACTIVATION_ACTIVE', false),

    // Daily batch sizes (newest-registered first). Days beyond the array
    // length reuse the last value. Gentle ramp protects sender reputation.
    'reactivation_ramp' => [150, 250, 350, 450, 600],

    // Single source of truth for the onboarding_email_log mail_key.
    'reactivation_mail_key' => 'reactivatie-2026-06',
];
