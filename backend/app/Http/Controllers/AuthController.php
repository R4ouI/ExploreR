<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
      $request->validate([
          'email'    => 'required|email',
          'password' => 'required',
      ]);

      $user = User::where('email', $request->email)->first();

      if (! $user) {
          return response()->json(['message' => 'USER_NOT_FOUND'], 401);
      }

      if (! Hash::check($request->password, $user->password)) {
          return response()->json(['message' => 'WRONG_PASSWORD'], 401);
      }

      $token = base64_encode($user->email . '|' . now());

      return response()->json([
          'message' => 'Login successful',
          'token'   => $token,
          'user'    => [
              'id'    => $user->id,
              'name'  => $user->name,
              'email' => $user->email,
          ],
      ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        // datorita casts() cu 'password' => 'hashed', parola se hasheaza automat
        $user = \App\Models\User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        $token = base64_encode($user->email . '|' . now());

        return response()->json([
            'message' => 'Register successful',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

}
