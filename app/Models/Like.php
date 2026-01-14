<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
        'profile_id',
        'type',
    ];

    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeLikes($query)
    {
        return $query->whereNull('type')->orWhere('type', 'like');
    }

    public function scopeBookmarks($query)
    {
        return $query->where('type', 'bookmark');
    }
}
