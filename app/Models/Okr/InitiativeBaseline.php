<?php

namespace App\Models\Okr;

use Database\Factories\Okr\InitiativeBaselineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeBaseline extends Model
{
    use HasFactory;

    protected $table = 'okr_initiative_baselines';

    protected $fillable = [
        'initiative_id',
        'key_result_id',
        'baseline_value',
        'baseline_unit',
        'baseline_at',
        'low_data',
    ];

    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:2',
            'baseline_at' => 'datetime',
            'low_data' => 'boolean',
        ];
    }

    protected static function newFactory(): InitiativeBaselineFactory
    {
        return InitiativeBaselineFactory::new();
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class, 'initiative_id');
    }

    public function keyResult(): BelongsTo
    {
        return $this->belongsTo(KeyResult::class, 'key_result_id');
    }
}
