<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'organisation',
        'function_title',
        'avatar_path',
        'bio',
        'website',
        'linkedin',
        'fiches_comments_seen_at',
        'terms_accepted_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fiches_comments_seen_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the URL for the user's avatar thumbnail (or full-size fallback).
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        $thumbPath = preg_replace('/\.(jpe?g|png|webp)$/i', '-thumb.webp', $this->avatar_path);

        if (Storage::disk('public')->exists($thumbPath)) {
            return Storage::url($thumbPath);
        }

        return Storage::url($this->avatar_path);
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'created_by');
    }

    public function fiches(): HasMany
    {
        return $this->hasMany(Fiche::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Like::class)->where('type', 'bookmark');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCurator(): bool
    {
        return $this->role === 'curator';
    }

    public function isContributor(): bool
    {
        return $this->role === 'contributor';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function newFicheCommentsCount(): int
    {
        return Comment::whereHasMorph('commentable', Fiche::class, fn ($q) => $q->where('user_id', $this->id))
            ->when($this->fiches_comments_seen_at, fn ($q) => $q->where('comments.created_at', '>', $this->fiches_comments_seen_at))
            ->count();
    }

    public function hasBookmarked(Fiche $fiche): bool
    {
        return $this->likes()
            ->where('likeable_type', Fiche::class)
            ->where('likeable_id', $fiche->id)
            ->where('type', 'bookmark')
            ->exists();
    }
}
