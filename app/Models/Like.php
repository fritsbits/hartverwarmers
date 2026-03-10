<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'likeable_type',
        'likeable_id',
        'type',
        'count',
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
        return $query->where('type', 'like');
    }

    public function scopeBookmarks($query)
    {
        return $query->where('type', 'bookmark');
    }

    public function scopeKudos($query)
    {
        return $query->where('type', 'kudos');
    }
}
