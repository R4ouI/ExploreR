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

    public function user(){ return $this->belongsTo(\App\Models\User::class); }
    public function likes(){ return $this->hasMany(\App\Models\RouteLike::class, 'route_id'); }
    public function comments(){ return $this->hasMany(\App\Models\RouteComment::class, 'route_id'); }


}
