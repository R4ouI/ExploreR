<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/generate-route', function () {

    // Punct de start - România (în viitor înlocuim cu locația userului)
    $startLat = 45.9432;
    $startLng = 24.9668;

    // Generăm 3-5 puncte random
    $points = [];
    $numPoints = rand(3, 6);    

    for ($i = 0; $i < $numPoints; $i++) {
        $points[] = [
            'lat' => $startLat + (rand(-100, 100) / 100),
            'lng' => $startLng + (rand(-100, 100) / 100),
        ];
    }

    return response()->json([
        'start' => ['lat' => $startLat, 'lng' => $startLng],
        'route' => $points,
    ]);
});
