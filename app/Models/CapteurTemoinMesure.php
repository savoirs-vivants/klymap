<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapteurTemoinMesure extends Model
{
    protected $fillable = ['capteur_temoin_id', 'enregistre_le', 'sht_temp', 'sht_hum', 'tmp_temp'];

    protected $casts = [
        'enregistre_le' => 'datetime',
    ];

    public function capteurTemoin(): BelongsTo
    {
        return $this->belongsTo(CapteurTemoin::class);
    }
}
