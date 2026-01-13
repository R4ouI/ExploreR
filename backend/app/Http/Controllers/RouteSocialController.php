<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SavedRoute;
use App\Models\RouteLike;
use App\Models\RouteComment;

class RouteSocialController extends Controller
{
    public function toggleLike(Request $request, string $slug)
    {
        $user = $request->user();
        $route = SavedRoute::where('slug', $slug)->firstOrFail();

        $existing = RouteLike::where('route_id', $route->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            RouteLike::create([
                'route_id' => $route->id,
                'user_id' => $user->id,
            ]);
        }

        $likesCount = RouteLike::where('route_id', $route->id)->count();

        return response()->json([
            'likes_count' => RouteLike::where('route_id', $route->id)->count(),
        ]);

    }

    public function listComments(string $slug)
    {
        $route = SavedRoute::where('slug', $slug)->firstOrFail();

        $items = RouteComment::query()
            ->where('route_id', $route->id)
            ->with(['user:id,name'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'user_name' => optional($c->user)->name ?? 'Anonymous',
                'created_at' => $c->created_at,
                'text' => $c->content, // frontend asteapta "text"
            ]);

        return response()->json(['items' => $items]);
    }

    public function postComment(Request $request, string $slug)
    {
        $user = $request->user();
        $route = SavedRoute::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        RouteComment::create([
            'route_id' => $route->id,
            'user_id' => $user->id,
            'content' => $data['text'],
        ]);

        return response()->json(['ok' => true], 201);
    }
}
