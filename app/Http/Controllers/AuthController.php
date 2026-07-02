<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:100',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
                'role'     => 'client',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registracija uspesna.',
                'token'   => $token,
                'user'    => $user,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registracija neuspesna.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Neispravni kredencijali.',
                ], 401);
            }

            if ($user->isBlocked()){
                return response()->json([
                    'success' => false,
                    'message' => 'Vas nalog je blokiran. Kontaktirajte admina'
                ], 403);
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Prijava uspesna.',
                'token'   => $token,
                'token_type' => 'Bearer', 
                'user'    => $user,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prijava neuspesna.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Odjava uspesna.',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Odjava neuspesna.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
  
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
    try {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        PasswordReset::where('email', $request->email)->delete();

        $token = \Illuminate\Support\Str::random(64);

        PasswordReset::create([
            'email'      => $request->email,
            'token'      => $token,
            'expires_at' => now()->addMinutes(30),
        ]);


        Mail::raw(
    "Vaš token za resetovanje lozinke je:\n\n{$token}\n\nToken važi 30 minuta.",
    function ($message) use ($request) {
        $message->to($request->email)
                ->subject('Resetovanje lozinke');
    }
);

        return response()->json([
            'success' => true,
            'message' => 'Ukoliko nalog postoji, poslaćemo vam email sa uputstvima.',
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate reset token.',
            'error'   => $e->getMessage(),
        ], 500);
    }

    
    }

    public function resetPassword(Request $request)
{
    try {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);


        $resetToken = PasswordReset::where('email', $request->email)->where('token', $request->token)->first();

        if (!$resetToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token.',
            ], 400);
        }

        // Provjeri da li je token istekao (30 minuta)
        if ($resetToken->isExpired()) {
            $resetToken->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please request a new one.',
            ], 400);
        }


        $user = User::where('email', $request->email)->first();
        $user->update(['password' => $request->password]);


        $resetToken->delete();

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please login again.',
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Password reset failed.',
            'error'   => $e->getMessage(),
        ], 500);
    }
    }
}