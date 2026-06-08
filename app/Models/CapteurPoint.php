<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapteurPoint extends Model
{
    protected $fillable = ['user_id', 'capteur_temoin_id', 'name', 'lat', 'lng', 'icu_value', 'std_dev', 'session_id', 'participant_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function capteurTemoin(): BelongsTo
    {
        return $this->belongsTo(CapteurTemoin::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(SessionParticipant::class, 'participant_id');
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(CapteurPointMesure::class);
    }
}
