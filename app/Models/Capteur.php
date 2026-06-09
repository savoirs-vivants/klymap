<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Capteur extends Model
{
    use HasFactory;

    protected $fillable = [
        'UID',
        'lat',
        'long',
        'temp',
        'hum',
        'vitesse_vent',
        'direction_vent',
        'press_baro',
        'pluie',
    ];

    public function mesures(): HasMany
    {
        return $this->hasMany(Mesure::class);
    }

    public function latestMesure(): HasOne
    {
        return $this->hasOne(Mesure::class)->latestOfMany();
    }
}
