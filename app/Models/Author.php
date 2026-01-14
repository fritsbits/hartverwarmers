<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Author extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'title',
        'description',
        'image',
        'company',
        'company_link',
        'linkedin',
        'email',
        'is_coach',
        'user_id',
        'carehome_id',
        'profile_id',
    ];

    protected $casts = [
        'is_coach' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
