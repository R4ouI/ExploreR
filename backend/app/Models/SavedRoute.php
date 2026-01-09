<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedRoute extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
