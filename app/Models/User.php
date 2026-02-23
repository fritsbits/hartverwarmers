<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'organisation_id',
        'function_title',
        'avatar_path',
        'bio',
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
            'password' => 'hashed',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'created_by');
    }

    public function elaborations(): HasMany
    {
        return $this->hasMany(Elaboration::class);
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

    public function hasBookmarked(Elaboration $elaboration): bool
    {
        return $this->likes()
            ->where('likeable_type', Elaboration::class)
            ->where('likeable_id', $elaboration->id)
            ->where('type', 'bookmark')
            ->exists();
    }
}
