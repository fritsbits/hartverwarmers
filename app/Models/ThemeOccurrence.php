<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_id',
        'year',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * @return array{label: string, emphatic: bool}|null
     */
    public function relativeBadge(?CarbonInterface $now = null): ?array
    {
        $now = ($now ?? today())->copy()->startOfDay();
        $start = $this->start_date->copy()->startOfDay();
        $end = ($this->end_date ?? $this->start_date)->copy()->startOfDay();

        if ($now->gt($start) && $now->lte($end)) {
            return ['label' => 'Loopt nu', 'emphatic' => true];
        }

        if ($now->equalTo($start)) {
            return ['label' => 'Vandaag', 'emphatic' => true];
        }

        if ($now->copy()->addDay()->equalTo($start)) {
            return ['label' => 'Morgen', 'emphatic' => false];
        }

        if ($start->gt($now)) {
            $days = (int) round($now->diffInDays($start));
            if ($days >= 2 && $days <= 6) {
                return ['label' => "Over {$days} dagen", 'emphatic' => false];
            }
        }

        return null;
    }
}
