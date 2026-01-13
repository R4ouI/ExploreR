<?php

namespace App\Http\Controllers;

use App\Models\SavedRoute;

class ShareFeedController extends Controller
{
    public function index()
    {
        $items = \App\Models\SavedRoute::query()
            ->with(['user:id,name'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'slug' => $r->slug,
                'user_name' => optional($r->user)->name ?? 'Anonymous',
                'created_at' => $r->created_at,
                'likes_count' => (int) $r->likes_count,
                'comments_count' => (int) $r->comments_count,
            ]);

        return response()->json(['items' => $items]);
    }


}
