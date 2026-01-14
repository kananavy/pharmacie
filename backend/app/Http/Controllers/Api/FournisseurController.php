<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index()
    {
        return Fournisseur::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string'
        ]);

        return Fournisseur::create($validated);
    }

    public function show(Fournisseur $fournisseur)
    {
        return $fournisseur;
    }

    public function update(Request $request, Fournisseur $fournisseur)
    {
        $validated = $request->validate([
            'nom' => 'string',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string'
        ]);

        $fournisseur->update($validated);
        return $fournisseur;
    }

    public function destroy(Fournisseur $fournisseur)
    {
        $fournisseur->delete();
        return response()->json(null, 204);
    }
}
