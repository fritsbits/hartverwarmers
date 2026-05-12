<?php

namespace App\Models;

use App\Enums\ThemeRecurrenceRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
