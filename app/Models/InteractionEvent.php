<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'fiche_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(Fiche::class);
    }
}
