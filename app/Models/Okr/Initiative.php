<?php

namespace App\Models\Okr;

use Database\Factories\Okr\InitiativeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Initiative extends Model
{
    use HasFactory;

    protected $table = 'okr_initiatives';

    protected $fillable = [
        'objective_id',
        'slug',
        'label',
        'status',
        'description',
        'started_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): InitiativeFactory
    {
        return InitiativeFactory::new();
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class, 'objective_id');
    }
}
