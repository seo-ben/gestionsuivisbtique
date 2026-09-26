<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** POST /api/auth/login */
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'password'  => 'required|string',
            'device_id' => 'nullable|string|max:100',
        ]);

        $user = User::where('telephone', $request->telephone)
                    ->where('actif', true)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'telephone' => ['Identifiants incorrects.'],
            ]);
        }

        // Révoquer les anciens tokens du même device si device_id fourni
        if ($request->device_id) {
            $user->tokens()->where('name', $request->device_id)->delete();
        }

        $tokenName = $request->device_id ?? 'mobile-' . time();
        $token     = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'        => $user->id,
                'uuid'      => $user->uuid,
                'nom'       => $user->nom,
                'telephone' => $user->telephone,
                'role'      => $user->role,
            ],
        ]);
    }

    /** POST /api/auth/logout */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }

    /** GET /api/auth/me */
    public function me(Request $request)
    {
        $user = $request->user()->load('boutiques');
        return response()->json($user);
    }
}
