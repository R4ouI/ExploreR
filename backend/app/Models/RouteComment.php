<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteComment extends Model
{
    protected $table = 'route_comments';

    protected $fillable = ['route_id', 'user_id', 'content'];

    public function route()
    {
        return $this->belongsTo(SavedRoute::class, 'route_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
