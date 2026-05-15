<?php

namespace App\Models\Okr;

use Database\Factories\Okr\KeyResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeyResult extends Model
{
    use HasFactory;

    protected $table = 'okr_key_results';

    protected $fillable = [
        'objective_id',
        'label',
        'metric_key',
        'target_value',
        'target_unit',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): KeyResultFactory
    {
        return KeyResultFactory::new();
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class, 'objective_id');
    }

    public function baselines(): HasMany
    {
        return $this->hasMany(InitiativeBaseline::class, 'key_result_id');
    }
}
