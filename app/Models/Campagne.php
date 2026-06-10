<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Campagne extends Model
{
    protected $fillable = ['nom', 'id_gestionnaire', 'nb_groupes', 'date_fin', 'code'];

    protected $casts = [
        'date_fin'   => 'date',
        'nb_groupes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($campagne) {
            $campagne->code = strtoupper(Str::random(8));
        });
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_gestionnaire');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class, 'id_session');
    }

    public function capteurPoints(): HasMany
    {
        return $this->hasMany(CapteurPoint::class, 'session_id');
    }

    public function capteurTemoins(): HasMany
    {
        return $this->hasMany(CapteurTemoin::class, 'session_id');
    }

    public function isTerminee(): bool
    {
        return $this->date_fin && $this->date_fin->isPast();
    }
}
