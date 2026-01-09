<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\SavedRoute;

class SavedRouteController extends Controller
{
    // POST /api/routes (protected)
    public function store(Request $request)
{
    $data = $request->validate([
        'payload' => ['required', 'array'],
        // optional: poti valida si intern, dar nu e obligatoriu
        // 'payload.type' => ['required', 'in:random,custom'],
    ]);

    $user = $request->user();

    $saved = SavedRoute::create([
        'user_id' => $user->id,
        'slug'    => (string) Str::uuid(),
        'payload' => $data['payload'], // ✅ aici e cheia: include si type
    ]);

    return response()->json([
        'slug' => $saved->slug,
        'share_url' => 'http://localhost:5173/share/' . $saved->slug,
    ], 201);
}

    // GET /api/routes/{slug} (public)
    public function show(string $slug)
    {
        $saved = SavedRoute::where('slug', $slug)->firstOrFail();

        return response()->json([
            'slug' => $saved->slug,
            'payload' => $saved->payload,
            'created_at' => $saved->created_at,
        ]);
    }

    // GET /api/routes (protected) - lista "ale mele"
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $routes = SavedRoute::where('user_id', $user->id)
            ->latest()
            ->select(['id', 'slug', 'payload', 'created_at'])
            ->paginate(20);

        return response()->json($routes);
    }

    // DELETE /api/routes/{slug} (protected)
    public function destroy(Request $request, string $slug)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $saved = SavedRoute::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $saved->delete();

        return response()->json(['ok' => true]);
    }
}
