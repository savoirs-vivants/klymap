<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapteurTemoin extends Model
{
    protected $fillable = ['user_id', 'name', 'images', 'date', 'lat', 'lng', 'session_id'];

    protected $casts = [
        'date'   => 'date',
        'images' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class, 'session_id');
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(CapteurTemoinMesure::class);
    }
}
