<?php

namespace App\Models;

use App\Enums\ThemeRecurrenceRule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_month',
        'recurrence_rule',
        'recurrence_detail',
    ];

    protected function casts(): array
    {
        return [
            'is_month' => 'boolean',
            'recurrence_rule' => ThemeRecurrenceRule::class,
        ];
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(ThemeOccurrence::class);
    }

    public function fiches(): BelongsToMany
    {
        return $this->belongsToMany(Fiche::class)->withTimestamps();
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        $first = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $last = $first->endOfMonth();

        return $query->whereHas('occurrences', function (Builder $q) use ($first, $last) {
            $q->where('start_date', '<=', $last)
                ->where(function (Builder $inner) use ($first) {
                    $inner->whereNotNull('end_date')->where('end_date', '>=', $first)
                        ->orWhere(function (Builder $sameDay) use ($first) {
                            $sameDay->whereNull('end_date')->where('start_date', '>=', $first);
                        });
                });
        });
    }
}
