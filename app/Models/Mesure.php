<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mesure extends Model
{
    use HasFactory;

    protected $fillable = [
        'capteur_id',
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

    public function capteur()
    {
        return $this->belongsTo(CapteurMeteo::class, 'capteur_id');
    }
}
