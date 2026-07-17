<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks which product updates were included in a user's monthly digest,
 * so a recipient never gets the same update twice. `sent_at` is the sole
 * timestamp column (same pattern as OnboardingEmailLog).
 */
class ProductUpdateSend extends Model
{
    const UPDATED_AT = null;

    const CREATED_AT = null;

    protected $fillable = ['user_id', 'update_uid', 'sent_at'];

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
