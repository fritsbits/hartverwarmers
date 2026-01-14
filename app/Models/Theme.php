<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Theme extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'is_month',
        'date_type',
    ];

    protected $casts = [
        'start' => 'date',
        'end' => 'date',
        'is_month' => 'boolean',
    ];

    public function isFixed(): bool
    {
        return $this->date_type === 'fixed';
    }

    public function isVariable(): bool
    {
        return $this->date_type === 'variable';
    }

    public function rolloverToNextYear(): void
    {
        if (!$this->isFixed() || !$this->start) {
            return;
        }

        $duration = $this->end ? $this->start->diffInDays($this->end) : null;

        $this->start = $this->start->addYear();

        if ($duration !== null) {
            $this->end = $this->start->copy()->addDays($duration);
        }

        $this->save();
    }

    public function shouldRollover(): bool
    {
        return $this->isFixed() && $this->start && $this->start->isPast();
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_theme');
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start', '<=', $now)->where('end', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start', '>', now())->orderBy('start');
    }

    public function scopeActiveOrUpcoming($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->where('start', '>=', $now)
              ->orWhere(function ($q2) use ($now) {
                  $q2->where('start', '<=', $now)
                     ->where(function ($q3) use ($now) {
                         $q3->whereNull('end')
                            ->orWhere('end', '>=', $now);
                     });
              });
        })->orderBy('start');
    }

    public function scopeNeedsRollover($query)
    {
        return $query->where('date_type', 'fixed')
                     ->whereNotNull('start')
                     ->where('start', '<', now());
    }
}
