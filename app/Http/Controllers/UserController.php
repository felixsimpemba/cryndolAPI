<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Deprecated: This endpoint documentation was replaced by AuthController::registerPersonal
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::create($request->all());
        return response()->json($user, 201);
    }
    public function login(Request $request){
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        if(!Hash::check($request->password, $user->password)){
            return response()->json(['message' => 'Invalid password'], 401);
        }
        return response()->json(['message' => 'Login successful'], 200);
    }
}
