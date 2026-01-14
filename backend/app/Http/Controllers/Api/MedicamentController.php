<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicamentController extends Controller
{
    public function index()
    {
        return Medicament::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'code' => 'required|string|unique:medicaments',
            'categorie' => 'nullable|string',
            'unite_emballage' => 'string',
            'quantite_par_emballage' => 'integer',
            'prix' => 'required|numeric',
            'prix_achat' => 'numeric',
            'stock' => 'required|integer',
            'date_expiration' => 'required|date',
            'ordonnance_requise' => 'required|boolean',
            'seuil_alerte' => 'integer',
            'max_stock' => 'integer',
            'emplacement' => 'nullable|string',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id'
        ]);

        return DB::transaction(function () use ($validated) {
            $medicament = Medicament::create($validated);

            // Record initial stock as movements
            if ($medicament->stock > 0) {
                MouvementStock::create([
                    'medicament_id' => $medicament->id,
                    'quantite' => $medicament->stock,
                    'type' => 'reception',
                    'motif' => 'Initialisation du stock',
                    'user_id' => auth()->id() ?? 1 // Fallback for seeders/dev
                ]);
            }

            return $medicament;
        });
    }

    public function show(Medicament $medicament)
    {
        $medicament->load('mouvements.user');
        if ($medicament->fournisseur_id) {
            $medicament->load('fournisseur');
        }
        return $medicament;
    }

    public function update(Request $request, Medicament $medicament)
    {
        $validated = $request->validate([
            'nom' => 'string',
            'code' => 'string|unique:medicaments,code,' . $medicament->id,
            'categorie' => 'nullable|string',
            'unite_emballage' => 'string',
            'quantite_par_emballage' => 'integer',
            'prix' => 'numeric',
            'prix_achat' => 'numeric',
            'stock' => 'integer',
            'date_expiration' => 'date',
            'ordonnance_requise' => 'boolean',
            'seuil_alerte' => 'integer',
            'max_stock' => 'integer',
            'emplacement' => 'nullable|string',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id'
        ]);

        $medicament->update($validated);
        return $medicament;
    }

    public function ajusterStock(Request $request, Medicament $medicament)
    {
        $validated = $request->validate([
            'quantite' => 'required|integer',
            'type' => 'required|in:reception,ajustement,retour',
            'motif' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($validated, $medicament) {
            $medicament->increment('stock', $validated['quantite']);

            MouvementStock::create([
                'medicament_id' => $medicament->id,
                'quantite' => $validated['quantite'],
                'type' => $validated['type'],
                'motif' => $validated['motif'],
                'user_id' => auth()->id()
            ]);

            return $medicament;
        });
    }

    public function destroy(Medicament $medicament)
    {
        $medicament->delete();
        return response()->json(null, 204);
    }

    public function alerts()
    {
        $stockAlerts = Medicament::whereColumn('stock', '<=', 'seuil_alerte')->get();
        $expirationAlerts = Medicament::where('date_expiration', '<=', now()->addDays(30))->get();

        return response()->json([
            'stock_alerts' => $stockAlerts,
            'expiration_alerts' => $expirationAlerts
        ]);
    }
}
