<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserController extends Controller 
{
    public function login(Request $request) {
        $request->validate([
            'email'=>'required|email',
            'password'=>'required'
        ]);

        $user = User::where('email',$request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success'=>false,'message'=>'Identifiants invalides']);
        }

        return response()->json([
            'success'=>true,
            'user'=>$user->load('roles.permissions')
        ]);
    }
}
