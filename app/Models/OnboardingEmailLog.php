<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks which onboarding emails have been sent (or evaluated) per user.
 *
 * The table uses `sent_at` as its sole timestamp column. Both CREATED_AT
 * and UPDATED_AT are disabled so Eloquent does not attempt to write to
 * non-existent `created_at`/`updated_at` columns. `sent_at` must always
 * be provided explicitly on creation.
 *
 * Note: a `mail_3` log entry may exist even when no notification was sent
 * (when the user already had a published fiche at evaluation time). The log
 * entry records that day-14 evaluation has occurred, preventing re-evaluation.
 */
class OnboardingEmailLog extends Model
{
    protected $table = 'onboarding_email_log';

    const UPDATED_AT = null;

    const CREATED_AT = null;

    protected $fillable = ['user_id', 'mail_key', 'sent_at'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
