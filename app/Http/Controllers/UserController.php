<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = User::with('accounts');

            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $users = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Korisnici su uspešno učitani.',
                'data'    => $users,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greška pri učitavanju korisnika.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:100'],
                'email'    => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'role'     => ['nullable', Rule::enum(Role::class)],
                'phone'    => ['nullable', 'string', 'max:30', 'unique:users,phone'],
                'jmbg'     => ['nullable', 'string', 'size:13', 'unique:users,jmbg'],
                'address'  => ['nullable', 'string', 'max:255'],
            ]);

            $validated['is_active'] = true;

            $user = User::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Korisnik je uspešno kreiran.',
                'data'    => $user,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kreiranje korisnika nije uspelo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function show(string $id)
    {
        try {
            $user = User::with('accounts')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detalji korisnika su uspešno učitani.',
                'data'    => $user,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Korisnik nije pronađen.',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }


    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name'    => ['sometimes', 'string', 'max:100'],
                'email'   => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'password'=> ['sometimes', 'string', 'min:8'],
                'role'    => ['sometimes', Rule::enum(Role::class)],
                'phone'   => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
                'jmbg'    => ['nullable', 'string', 'size:13', Rule::unique('users', 'jmbg')->ignore($user->id)],
                'address' => ['nullable', 'string', 'max:255'],
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Korisnik je uspešno ažuriran.',
                'data'    => $user->fresh(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ažuriranje korisnika nije uspelo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

 
    public function destroy(string $id)
    {
        try {
            $user = User::with('accounts')->findOrFail($id);

            if ($user->accounts->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Korisnik ne može biti obrisan dok ima aktivne račune.',
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Korisnik je uspešno obrisan.',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brisanje korisnika nije uspelo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function block(string $id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->isBlocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Korisnik je već blokiran.',
                ], 422);
            }

            $user->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Korisnik je uspešno blokiran.',
                'data'    => $user->fresh(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Blokiranje korisnika nije uspelo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

  
    public function unblock(string $id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Korisnik je već aktivan.',
                ], 422);
            }

            $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Korisnik je uspešno odblokiran.',
                'data'    => $user->fresh(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Odblokiralnje korisnika nije uspelo.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

  
    public function changeRole(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'role' => ['required', Rule::enum(Role::class)],
            ]);

            $user->update(['role' => $validated['role']]);

            return response()->json([
                'success' => true,
                'message' => 'Uloga korisnika je uspešno promenjena.',
                'data'    => $user->fresh(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promena uloge nije uspela.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function changePassword(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'current_password' => ['required', 'string'],
                'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trenutna lozinka nije tačna.',
                ], 422);
            }

            $user->update(['password' => $request->new_password]);

            return response()->json([
                'success' => true,
                'message' => 'Lozinka je uspešno promenjena.',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promena lozinke nije uspela.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}