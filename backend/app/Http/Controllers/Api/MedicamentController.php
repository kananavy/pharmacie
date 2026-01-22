<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Lot;

class MedicamentController extends Controller
{
    public function index(Request $request)
    {
        $query = Medicament::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Eager load lots count and sum of quantity
        $medicaments = $query->withSum('lots', 'quantite_actuelle')->get();

        return $medicaments;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|unique:medicaments,nom',
            'code' => 'nullable|string|unique:medicaments,code',
            'categorie' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'prix_achat' => 'nullable|numeric|min:0',
            'ordonnance_requise' => 'required|boolean',
            'seuil_alerte' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'emplacement' => 'nullable|string',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'unites_par_boite' => 'nullable|integer|min:1',
            'prix_unitaire' => 'nullable|numeric|min:0',
        ]);

        // Creating a medicament does NOT create stock. Stock is added by creating lots.
        $medicament = Medicament::create($validated);

        return response()->json($medicament, 201);
    }

    public function show(Medicament $medicament)
    {
        // Load lots with their fournisseur, and also the medicament's base fournisseur and mouvements
        $medicament->load(['lots.fournisseur', 'fournisseur', 'mouvements.user']);
        return $medicament;
    }

    public function update(Request $request, Medicament $medicament)
    {
        $validated = $request->validate([
            'nom' => 'string|unique:medicaments,nom,' . $medicament->id,
            'code' => 'nullable|string|unique:medicaments,code,' . $medicament->id,
            'categorie' => 'nullable|string',
            'prix' => 'numeric|min:0',
            'prix_achat' => 'nullable|numeric|min:0',
            'ordonnance_requise' => 'boolean',
            'seuil_alerte' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'emplacement' => 'nullable|string',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'unites_par_boite' => 'nullable|integer|min:1',
            'prix_unitaire' => 'nullable|numeric|min:0',
        ]);

        $medicament->update($validated);
        return $medicament;
    }



    public function destroy(Medicament $medicament)
    {
        $medicament->delete();
        return response()->json(null, 204);
    }

    public function alerts()
    {
        // Stock alerts: Find medicaments where the sum of their lots' quantities is below the alert threshold.
        $stockAlerts = Medicament::whereNotNull('seuil_alerte')
            ->where('seuil_alerte', '>', 0)
            ->withSum('lots', 'quantite_actuelle')
            ->get()
            ->filter(function ($medicament) {
                return $medicament->lots_sum_quantite_actuelle <= $medicament->seuil_alerte;
            })->values();

        // Expiration alerts: Find lots expiring within 30 days
        $expirationAlerts = Lot::with('medicament')
            ->where('quantite_actuelle', '>', 0)
            ->where('date_expiration', '<=', now()->addDays(30))
            ->where('date_expiration', '>', now()) // Optional: exclude already expired
            ->orderBy('date_expiration', 'asc')
            ->get();

        return response()->json([
            'stock_alerts' => $stockAlerts,
            'expiration_alerts' => $expirationAlerts
        ]);
    }
}
