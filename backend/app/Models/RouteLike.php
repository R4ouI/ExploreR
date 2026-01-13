<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteLike extends Model
{
    protected $fillable = ['route_id', 'user_id'];

    public function route()
    {
        return $this->belongsTo(SavedRoute::class, 'route_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
