<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    public function initiatives(): MorphToMany
    {
        return $this->morphedByMany(Initiative::class, 'taggable');
    }

    public function fiches(): MorphToMany
    {
        return $this->morphedByMany(Fiche::class, 'taggable');
    }
}
