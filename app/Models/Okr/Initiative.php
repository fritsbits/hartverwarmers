<?php

namespace App\Models\Okr;

use App\Services\Okr\BaselineCapturer;
use Database\Factories\Okr\InitiativeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected static function booted(): void
    {
        static::saved(function (Initiative $initiative): void {
            if ($initiative->started_at !== null) {
                app(BaselineCapturer::class)->captureFor($initiative);
            }
        });
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class, 'objective_id');
    }

    public function baselines(): HasMany
    {
        return $this->hasMany(InitiativeBaseline::class, 'initiative_id');
    }
}
