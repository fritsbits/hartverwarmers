<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Elaboration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'initiative_id',
        'user_id',
        'title',
        'slug',
        'description',
        'practical_tips',
        'fiche',
        'target_audience',
        'published',
        'has_diamond',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'fiche' => 'array',
            'target_audience' => 'array',
            'published' => 'boolean',
            'has_diamond' => 'boolean',
        ];
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class)->orderBy('sort_order');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
