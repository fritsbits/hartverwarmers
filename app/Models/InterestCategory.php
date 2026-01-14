<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title',
        'icon',
        'color',
    ];

    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class, 'interest_category_id');
    }

    public function domainInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'interest_category_id')->where('type', 'domain');
    }
}
