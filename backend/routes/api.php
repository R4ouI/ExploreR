<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use GuzzleHttp\Client;
use App\Providers\KeyManager; //pentru singletone


Route::get('/generate-random-route', function (Request $request) {

    $start = explode(',', $request->query('start'));
    $distance = floatval($request->query('length'));
    $terrain = $request->query('terrain');
    $loop = filter_var($request->query('loop'), FILTER_VALIDATE_BOOLEAN);
    if(count($start) !== 2){
        return response()->json(['error' => 'Invalid start coordinates'], 400);
    }
    if ($loop==1)
        $distance = $distance/2;//daca e loop atunci jumatate din distanta o sa fie dus intors
    $startLng = floatval($start[0]);
    $startLat = floatval($start[1]);
    $unghiGrade = rand(0, 360);//unghi random in care sa mearga ruta
    $unghiRad = deg2rad($unghiGrade);//convertim în radiani sa mearga sin cos
    $distantaLng= ($distance/78)* sin($unghiRad);//un grad de longitudine e aproximativ 78 km
    $distantaLat= ($distance/111)* cos($unghiRad);//un grad de latitudine e aproximativ 111 km
    $endLng = $startLng + $distantaLng;
    $endLat = $startLat + $distantaLat;

    $apiKey = KeyManager::getInstance()->getKey();
    if ($loop==0)
        $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key={$apiKey}&start={$startLng},{$startLat}&end={$endLng},{$endLat}";
    else
    {
        $coordonateRuta = "{$startLng},{$startLat};{$endLng},{$endLat};{$startLng},{$startLat}";
        $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key={$apiKey}&coordinates={$coordonateRuta}";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/geo+json;charset=UTF-8"
    ]);

    $response = curl_exec($ch);
    if($response === false){//daca avem erori
        $error = curl_error($ch);
        curl_close($ch);
        return response()->json(['error' => $error]);
    }

    if (isset($data['error'])) {
        return response()->json(['ors_error' => $data['error']], 500, [], JSON_PRETTY_PRINT);
}

    curl_close($ch);
    $data = json_decode($response, true);//contine tot raspunsul
    $route = $data['features'][0]['geometry']['coordinates'] ?? [];//ia da lista de coordonate din data
    return response()->json([
        'start' => [$startLng, $startLat],
        'end'   => [$endLng, $endLat],
        'route' => $route
    ], 200, [], JSON_PRETTY_PRINT);
});







Route::get('/generate-custom-route', function (Request $request) {
    $start = explode(',', $request->query('start'));
    $end   = explode(',', $request->query('end'));

    if(count($start) !== 2 || count($end) !== 2){
        return response()->json(['error' => 'Invalid start or end coordinates'], 400);
    }

    $startLng = floatval($start[0]);
    $startLat = floatval($start[1]);
    $endLng   = floatval($end[0]);
    $endLat   = floatval($end[1]);

    $apiKey = KeyManager::getInstance()->getKey();
    $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key={$apiKey}&start={$startLng},{$startLat}&end={$endLng},{$endLat}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/geo+json;charset=UTF-8"
    ]);
    $response = curl_exec($ch);
    if($response === false){
        $error = curl_error($ch);
        curl_close($ch);
        return response()->json(['error' => $error]);
    }
    curl_close($ch);

    $data = json_decode($response, true);
    $route = $data['features'][0]['geometry']['coordinates'] ?? [];
    return response()->json([
        'start' => [$startLat, $startLng],
        'end'   => [$endLat, $endLng],
        'route' => $route
    ], 200, [], JSON_PRETTY_PRINT);
});




Route::get('/test-key', function() {
    return response()->json(['api_key' => KeyManager::getInstance()->getKey()]);
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


Route::get('/simple-route', function () {
    $start = "8.681495,49.41461";
    $end   = "8.687872,49.420318";
    $apiKey = KeyManager::getInstance()->getKey();

    $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key={$apiKey}&start={$start}&end={$end}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/geo+json;charset=UTF-8"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    if($response === false){
        $error = curl_error($ch);
        curl_close($ch);
        return response()->json(['error' => $error]);
    }
    curl_close($ch);
    $data = json_decode($response, true);
    return response()->json($data, 200, [], JSON_PRETTY_PRINT);
});




Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);




