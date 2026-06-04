<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapteurPointMesure extends Model
{
    protected $fillable = ['capteur_point_id', 'enregistre_le', 'sht_temp', 'sht_hum', 'tmp_temp', 'excluded'];

    protected $casts = [
        'enregistre_le' => 'datetime',
        'excluded'      => 'boolean',
    ];

    public function capteurPoint(): BelongsTo
    {
        return $this->belongsTo(CapteurPoint::class);
    }
}
