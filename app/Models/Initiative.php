<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Initiative extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'image',
        'diamant_guidance',
        'published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'diamant_guidance' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fiches(): HasMany
    {
        return $this->hasMany(Fiche::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function thumbnailUrl(): ?string
    {
        return $this->image
            ? Str::replaceLast('.webp', '-thumb.webp', $this->image)
            : null;
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
