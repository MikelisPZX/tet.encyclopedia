<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'cca2',
        'cca3',
        'name_common',
        'name_official',
        'population',
        'population_rank',
        'flag_url',
        'flag_emoji',
        'area',
        'translations',
        'borders',
        'languages',
    ];

    protected $casts = [
        'translations' => 'array',
        'borders' => 'array',
        'languages' => 'array',
    ];

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function isFavorite()
    {
        return $this->favorites()->exists();
    }

    public function neighbors()
    {
        if (!$this->borders) {
            return collect([]);
        }
        
        return Country::whereIn('cca3', $this->borders)->get();
    }
}
