<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'dimensions',
        'guidances',
        'fiche',
        'target_audience',
        'published',
        'shared',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'guidances' => 'array',
        'fiche' => 'array',
        'target_audience' => 'array',
        'published' => 'boolean',
        'shared' => 'boolean',
    ];

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'activity_interest');
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class, 'activity_theme');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable')->where('type', 'bookmark');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeShared($query)
    {
        return $query->where('shared', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
