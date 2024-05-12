<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $field = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $request->input('login'), 'password' => $request->input('password')];
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            return response()->json([
                'message' => 'Logged in successfully',
                'user' => $user,
            ], 200);
        }
        return response()->json(['error' => 'The provided credentials do not match our records.'], 401);
    }
}
