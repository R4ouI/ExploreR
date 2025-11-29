<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use GuzzleHttp\Client;

Route::get('/generate-random-route', function (Request $request) {
    $start = explode(',', $request->query('start')); 
    $startLng = floatval($start[0]);//longitudine
    $startLat = floatval($start[1]);//latitudine

    $client = new Client([
    'verify' => false 
    ]);
    $url = 'https://api.openrouteservice.org/v2/directions/driving-car';

    $endLat = $startLat + (rand(-10, 10)/100);
    $endLng = $startLng + (rand(-10, 10)/100);

    $response = $client->get($url, [
        'query' => [
            'start' => $startLng.','.$startLat, 
            'end'   => $endLng.','.$endLat,
        ],
        'headers' => [
            'Authorization' => env('ORS_API_KEY'),
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    $route = $data['features'][0]['geometry']['coordinates']; 

    return response()->json([
        'start' => [$startLng, $startLat],
        'route' => $route,
    ]);
});






Route::get('/generate-custom-route', function (Request $request) {//ORS
    $start = explode(',', $request->query('start'));
    $end   = explode(',', $request->query('end'));

    if(count($start) !== 2 || count($end) !== 2){
        return response()->json(['error' => 'Invalid start or end coordinates'], 400);
    }

    $startLng = floatval($start[0]);
    $startLat = floatval($start[1]);
    $endLng   = floatval($end[0]);
    $endLat   = floatval($end[1]);

    $client = new Client(['verify' => false]);

    try {
        $response = $client->post('https://api.openrouteservice.org/v2/directions/driving-car', [
            'headers' => [
                'Authorization' => env('ORS_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'coordinates' => [
                    [$startLng, $startLat],
                    [$endLng, $endLat]
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);//aici sunt toate datele rezultate

        if(!isset($data['features'][0]['geometry']['coordinates'])){//aici am coordonatele rutei rezultate
            return response()->json(['error' => 'No route returned from ORS'], 500);
        }

        // Inversăm coordonatele pentru Leaflet: [lat, lng]
        $route = array_map(fn($p) => ['lat' => $p[1], 'lng' => $p[0]], $data['features'][0]['geometry']['coordinates']);

        return response()->json([
            'start' => ['lat' => $startLat, 'lng' => $startLng],
            'end'   => ['lat' => $endLat, 'lng' => $endLng],
            'route' => $route,
        ]);

    } catch (\GuzzleHttp\Exception\RequestException $e) {
    $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
    return response()->json([
        'error' => 'OpenRouteService request failed',
        'message' => $responseBody
    ], 500);
    }
});


Route::get('/test-key', function() {
    return response()->json(['api_key' => env('ORS_API_KEY')]);
});

Route::get('/generate-custom-route-verificare', function () {
    return response()->json([
        'route' => [
            ['lat' => 46.7700, 'lng' => 23.5900],
            ['lat' => 46.7800, 'lng' => 23.6000],
            ['lat' => 46.7900, 'lng' => 23.6100],
        ]
    ]);
});




Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);




