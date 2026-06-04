<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionParticipant extends Model
{
    protected $fillable = ['id_session', 'id_groupe', 'pseudo'];

    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'id_session');
    }

    public function point()
    {
        return $this->hasMany(Point::class, 'participant_id');
    }
}
