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
        'DevEui',
        'lat',
        'long',
        'temp',
        'hum',
        'vitesse_vent',
        'press_baro',
        'pluie',
        'indice_chaleur',
        'debit_pluie',
        'densite_air',
        'evapotranspiration',
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
