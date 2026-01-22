<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Enforce SuperAdmin check in a real app, middleware recommended.
        // For now, allow admin/superadmin roles.
        // Assuming 'admin' acts as SuperAdmin based on user request "SuperAdmin" with user management.
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return User::latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:superadmin,admin,vendeur,caissier'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role']
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'role' => 'in:superadmin,admin,vendeur,caissier',
            'password' => 'nullable|string|min:8'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Impossible de supprimer son propre compte'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}
