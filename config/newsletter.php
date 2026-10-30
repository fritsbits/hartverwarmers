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

    /*
     * One-off call to every contributor to help reach a fiche milestone.
     * Sends stop by themselves once the published count reaches the target.
     */
    'milestone_call_active' => env('NEWSLETTER_MILESTONE_CALL_ACTIVE', false),

    'milestone_call_target' => 500,

    'milestone_call_mail_key' => 'naar-500-2026-11',
];
