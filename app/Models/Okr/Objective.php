<?php

namespace App\Models\Okr;

use Database\Factories\Okr\ObjectiveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Objective extends Model
{
    use HasFactory;

    protected $table = 'okr_objectives';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'status',
        'position',
    ];

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
}
