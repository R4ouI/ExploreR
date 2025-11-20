<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/generate-route', function () {

    $startLat = 45.9432;
    $startLng = 24.9668;

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
        'route'  => $points,
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);