<?php

namespace App\Models;

use Database\Factories\EmailBounceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailBounce extends Model
{
    /** @use HasFactory<EmailBounceFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
        'type',
        'reason',
        'bounced_at',
    ];

    protected function casts(): array
    {
        return [
            'bounced_at' => 'datetime',
        ];
    }
}
