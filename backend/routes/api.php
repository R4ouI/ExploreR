<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::get('/generate-random-route', function (Request $request) {
    $start = explode(',', $request->query('start')); 
    $startLat = floatval($start[0]);
    $startLng = floatval($start[1]);

    //verificare cu pct de start si 2 random
     $points = [
        ['lat' => $startLat, 'lng' => $startLng],
        ['lat' => $startLat + (rand(-10, 10) / 100), 'lng' => $startLng + (rand(-10, 10) / 100)],
        ['lat' => $startLat + (rand(-10, 10) / 100), 'lng' => $startLng + (rand(-10, 10) / 100)],
    ];

    return response()->json([
        'start' => ['lat' => $startLat, 'lng' => $startLng],
        'route' => $points,
    ]);
});

Route::get('/generate-custom-route', function (Request $request) {
    $start = explode(',', $request->query('start'));
    $end = explode(',', $request->query('end'));

    $startLat = floatval($start[0]);
    $startLng = floatval($start[1]);
    $endLat = floatval($end[0]);
    $endLng = floatval($end[1]);

    $points = [
        ['lat' => $startLat, 'lng' => $startLng],
        ['lat' => $endLat, 'lng' => $endLng],
    ];

    return response()->json([
        'start' => ['lat' => $startLat, 'lng' => $startLng],
        'end' => ['lat' => $endLat, 'lng' => $endLng],
        'route' => $points,
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
