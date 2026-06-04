<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $fillable = ['nom', 'lat', 'long', 'icu', 'temoin_id'];


}
