<?php

namespace App\Models\Okr;

use Database\Factories\Okr\ObjectiveFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Objective extends Model
{
    use HasFactory;

    protected $table = 'okr_objectives';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'status',
        'archived_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ObjectiveFactory
    {
        return ObjectiveFactory::new();
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class, 'objective_id')->orderBy('position');
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class, 'objective_id')->orderBy('position');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** @param  Builder<self>  $query */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * An objective has no start date of its own: it starts when its first
     * initiative starts. Null while nothing has been started yet.
     */
    public function startedAt(): ?Carbon
    {
        $earliest = $this->relationLoaded('initiatives')
            ? $this->initiatives->whereNotNull('started_at')->min('started_at')
            : $this->initiatives()->whereNotNull('started_at')->min('started_at');

        if ($earliest === null) {
            return null;
        }

        return $earliest instanceof Carbon ? $earliest : Carbon::parse($earliest);
    }
}
