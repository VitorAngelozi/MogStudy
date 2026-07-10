<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials)) {
            return response()->json(['message' => 'Login successful']);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
