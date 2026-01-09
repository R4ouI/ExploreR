<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Missing token'], 401);
        }

        $token = trim(substr($header, 7));
        $decoded = base64_decode($token, true);

        if ($decoded === false || strpos($decoded, '|') === false) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        [$email] = explode('|', $decoded, 2);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid user'], 401);
        }

        // atasam user-ul pe request (simplu, fara sesiuni)
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
