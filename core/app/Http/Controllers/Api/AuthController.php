<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class AuthController extends Controller {
    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides'
            ], 401);
        }

        // Crée un token simple (ou utiliser Laravel Sanctum)
        $token = Str::random(60);

        // Enregistrer le token dans la base de données si nécessaire
        $user->api_token = hash('sha256', $token);
        $user->save();

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token
        ]);
    }
}