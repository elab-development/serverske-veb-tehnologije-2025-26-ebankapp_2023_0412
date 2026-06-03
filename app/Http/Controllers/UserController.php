<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::with('accounts')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::enum(Role::class)],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'jmbg' => ['nullable', 'string', 'size:13', 'unique:users,jmbg'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = User::with('accounts')->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', Rule::enum(Role::class)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'jmbg' => ['nullable', 'string', 'size:13', Rule::unique('users', 'jmbg')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function block(string $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Korisnik je blokiran.',
            'user' => $user,
        ]);
    }

    public function changeRole(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Uloga korisnika je promenjena.',
            'user' => $user,
        ]);
    }
}