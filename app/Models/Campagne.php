<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campagne extends Model
{
    protected $fillable = ['nom', 'id_gestionnaire', 'nb_groupes', 'date_fin'];

    protected $casts = [
        'date_fin'   => 'date',
        'nb_groupes' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($campagne) {
            $campagne->code = strtoupper(Str::random(8));
        });
    }

    public function participants()
    {
        return $this->hasMany(SessionParticipant::class, 'id_session');
    }

    public function point()
    {
        return $this->hasMany(Point::class, 'session_id');
    }
}
