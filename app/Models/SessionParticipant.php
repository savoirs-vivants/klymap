<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionParticipant extends Model
{
    protected $fillable = ['id_session', 'id_groupe', 'pseudo'];

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class, 'id_session');
    }

    public function capteurPoints(): HasMany
    {
        return $this->hasMany(CapteurPoint::class, 'participant_id');
    }

    public function groupeLabel(): string
    {
        return $this->id_groupe > 0 ? 'Groupe ' . chr(64 + $this->id_groupe) : 'Individuel';
    }
}
