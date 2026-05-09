<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MozoSession extends Model
{
    protected $fillable = [
        'user_id',
        'nombre_mozo',
        'session_date',
        'session_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
