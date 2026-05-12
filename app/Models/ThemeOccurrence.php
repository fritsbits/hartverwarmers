<?php

namespace App\Models;

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
}
