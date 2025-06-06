<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'country_name',
        'flag_emoji',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
