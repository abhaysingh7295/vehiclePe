<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json(['token' => $token]);
}

public function me(Request $request)
{
    return response()->json($request->user());
}

public function logout(Request $request)
{
    JWTAuth::invalidate($request->token);

    return response()->json(['message' => 'Successfully logged out']);
}
}
