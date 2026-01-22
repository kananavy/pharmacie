<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Medicament;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LotController extends Controller
{
    /**
     * Store a newly created lot in storage.
     * This represents receiving new stock.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medicament_id' => 'required|exists:medicaments,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'numero_lot' => 'required|string|max:255',
            'quantite' => 'required|integer|min:1',
            'prix_achat' => 'required|numeric|min:0',
            'date_fabrication' => 'nullable|date',
            'date_expiration' => 'required|date|after:today',
        ]);

        $lot = Lot::create([
            'medicament_id' => $validated['medicament_id'],
            'fournisseur_id' => $validated['fournisseur_id'] ?? null,
            'numero_lot' => $validated['numero_lot'],
            'quantite_initiale' => $validated['quantite'],
            'quantite_actuelle' => $validated['quantite'],
            'prix_achat' => $validated['prix_achat'],
            'date_fabrication' => $validated['date_fabrication'] ?? null,
            'date_expiration' => $validated['date_expiration'],
        ]);

        // Log the stock movement
        MouvementStock::create([
            'medicament_id' => $lot->medicament_id,
            'quantite' => $lot->quantite_actuelle,
            'type' => 'reception',
            'motif' => 'Réception Lot: ' . $lot->numero_lot,
            'user_id' => Auth::id(),
        ]);

        return response()->json($lot, 201);
    }
}
