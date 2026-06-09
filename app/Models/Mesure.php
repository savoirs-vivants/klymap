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
        'direction_vent',
        'press_baro',
        'pluie',
    ];

    public function capteur()
    {
        return $this->belongsTo(Capteur::class);
    }
}
