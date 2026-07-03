<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DiamondRotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'fiche_id',
        'suggested_fiche_ids',
        'chosen_via',
        'suggestion_sent_at',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'suggested_fiche_ids' => 'array',
            'suggestion_sent_at' => 'datetime',
            'awarded_at' => 'datetime',
        ];
    }

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(Fiche::class);
    }

    public function scopeForMonth($query, Carbon $month)
    {
        return $query->whereDate('month', $month->copy()->startOfMonth()->toDateString());
    }

    public function isAwarded(): bool
    {
        return $this->awarded_at !== null;
    }

    public function monthLabel(): string
    {
        return $this->month->locale('nl_BE')->isoFormat('MMMM YYYY');
    }
}
