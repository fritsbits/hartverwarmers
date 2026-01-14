<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'interest_category_id',
        'parent_id',
        'type',
        'image',
        'icon',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InterestCategory::class, 'interest_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Interest::class, 'parent_id');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_interest');
    }

    public function scopeDomains($query)
    {
        return $query->where('type', 'domain');
    }

    public function scopeInterests($query)
    {
        return $query->where('type', 'interest');
    }
}
