<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Http;
use App\Providers\KeyManager;
use App\Http\Controllers\SavedRouteController;


function getCoordinatesFromORS($locationName) {
    if (strpos($locationName, ',') !== false) {
        $parts = explode(',', $locationName);
        $p0 = floatval(trim($parts[0]));
        $p1 = floatval(trim($parts[1]));
        if ($p0 != 0 && $p1 != 0) {
            return ['lng' => $p0, 'lat' => $p1];
        }
    }

    $apiKey = KeyManager::getInstance()->getKey();

    $url = "https://api.openrouteservice.org/geocode/search?api_key={$apiKey}&text=" . urlencode($locationName) . "&size=1";

    try {
        $response = Http::withoutVerifying()->get($url);
        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data['features'])) {
                $coords = $data['features'][0]['geometry']['coordinates'];
                return ['lng' => $coords[0], 'lat' => $coords[1]];
            }
        }
    } catch (\Exception $e) {
        logger()->error("ORS Geocoding Error: " . $e->getMessage());
    }

    return null;
}


Route::get('/generate-custom-route', function (Request $request) {

    $startInput = $request->query('start');
    $endInput   = $request->query('end');

    $mode = $request->query('mode', 'driving-car');
    $allowedModes = ['driving-car', 'cycling-regular', 'foot-hiking'];
    if (!in_array($mode, $allowedModes)) {
        $mode = 'driving-car';
    }

    $startCoords = getCoordinatesFromORS($startInput);
    $endCoords   = getCoordinatesFromORS($endInput);

    if (!$startCoords || !$endCoords) {
        return response()->json([
            'error' => 'Nu am putut găsi locațiile specificate. Verifică numele orașelor.'
        ], 400);
    }

    $startLng = $startCoords['lng'];
    $startLat = $startCoords['lat'];
    $endLng   = $endCoords['lng'];
    $endLat   = $endCoords['lat'];

    $apiKey = KeyManager::getInstance()->getKey();

    $url = "https://api.openrouteservice.org/v2/directions/{$mode}?api_key={$apiKey}&start={$startLng},{$startLat}&end={$endLng},{$endLat}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/geo+json;charset=UTF-8"]);

    $response = curl_exec($ch);

    if($response === false){
        $error = curl_error($ch);
        curl_close($ch);
        return response()->json(['error' => 'CURL Error: ' . $error], 500);
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['features']) || empty($data['features'])) {
        return response()->json([
            'status' => 'ORS_ERROR',
            'message' => 'Nu s-a găsit un drum între aceste puncte.',
            'ors_response' => $data
        ], 422);
    }

    $route = $data['features'][0]['geometry']['coordinates'] ?? [];

    return response()->json([
        'start' => [$startLng, $startLat],
        'end'   => [$endLng, $endLat],
        'route' => $route
    ], 200, [], JSON_PRETTY_PRINT);
});


Route::get('/generate-random-route', function (Request $request) {

    $startInput = $request->query('start');

    $mode = $request->query('mode', 'driving-car');
    $allowedModes = ['driving-car', 'cycling-regular', 'foot-hiking'];
    if (!in_array($mode, $allowedModes)) {
        $mode = 'driving-car';
    }

    $coords = getCoordinatesFromORS($startInput);

    if (!$coords) {
        return response()->json(['error' => 'Invalid start location'], 400);
    }

    $startLng = $coords['lng'];
    $startLat = $coords['lat'];

    $distance = floatval($request->query('length'));
    $loop = filter_var($request->query('loop'), FILTER_VALIDATE_BOOLEAN);

    if ($loop == 1) $distance = $distance/2;

    $apiKey = KeyManager::getInstance()->getKey();
    $maxAttempts = 100;
    $routeFound = false;
    $data = null;
    $endLng = null; $endLat = null;

    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $unghiGrade = rand(0, 360);
        $unghiRad = deg2rad($unghiGrade);
        $distantaLng= ($distance/78)* sin($unghiRad);
        $distantaLat= ($distance/111)* cos($unghiRad);
        $endLng = $startLng + $distantaLng;
        $endLat = $startLat + $distantaLat;

        $ch = curl_init();
        $httpHeaders = ["Accept: application/geo+json;charset=UTF-8"];
        $postData = null;

        if ($loop == 0) {
            $url = "https://api.openrouteservice.org/v2/directions/{$mode}?api_key={$apiKey}&start={$startLng},{$startLat}&end={$endLng},{$endLat}";
        } else {
            $url = "https://api.openrouteservice.org/v2/directions/{$mode}/geojson";
            $coordinates = [[$startLng, $startLat], [$endLng, $endLat], [$startLng, $startLat]];
            $postData = json_encode(['coordinates' => $coordinates]);
            $httpHeaders[] = "Content-Type: application/json";
            $httpHeaders[] = "Authorization: {$apiKey}";
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        $response = curl_exec($ch);

        if($response === false)
        {
            curl_close($ch);
            continue;
        }
        curl_close($ch);

        try {
            $data = json_decode($response, true);
        }
        catch (\Exception $e)
        {
            continue;
        }

        if (isset($data['features']) && !empty($data['features'])) {
            $routeFound = true;
            break;
        }
    }

    if (!$routeFound) { return response()->json(['status' => 'ROUTE_SEARCH_FAILED'], 404); }

    $route = $data['features'][0]['geometry']['coordinates'] ?? [];
    return response()->json([
        'start' => [$startLng, $startLat],
        'end'   => [$endLng, $endLat],
        'route' => $route
    ], 200);
});

Route::get('/test-key', function() {
    return response()->json(['api_key' => KeyManager::getInstance()->getKey()]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);// Public: acces pe share link

Route::get('/routes/{slug}', [SavedRouteController::class, 'show']);

// Protected: salveaza / lista / sterge
Route::middleware('api.token')->group(function () {
    Route::post('/routes', [SavedRouteController::class, 'store']);
    Route::get('/routes', [SavedRouteController::class, 'index']);
    Route::delete('/routes/{slug}', [SavedRouteController::class, 'destroy']);
});

