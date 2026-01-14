<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use App\Models\DetailVente;
use App\Models\Medicament;
use App\Models\Ordonnance;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    public function index()
    {
        return Vente::with(['user', 'details.medicament', 'ordonnance'])->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.medicament_id' => 'required|exists:medicaments,id',
            'items.*.quantite' => 'required|integer|min:1',
            'ordonnance' => 'nullable|array',
            'ordonnance.numero' => 'required_with:ordonnance|string',
            'ordonnance.medecin' => 'required_with:ordonnance|string',
            'ordonnance.date_ordonnance' => 'required_with:ordonnance|date',
        ]);

        return DB::transaction(function () use ($request) {
            $ordonnanceId = null;

            if ($request->has('ordonnance')) {
                $ordonnance = Ordonnance::create($request->ordonnance);
                $ordonnanceId = $ordonnance->id;
            }

            $total = 0;
            foreach ($request->items as $item) {
                $medicament = Medicament::findOrFail($item['medicament_id']);

                if ($medicament->stock < $item['quantite']) {
                    throw new \Exception("Stock insuffisant pour " . $medicament->nom);
                }

                if ($medicament->ordonnance_requise && !$ordonnanceId) {
                    throw new \Exception("Ordonnance requise pour " . $medicament->nom);
                }

                $total += $medicament->prix * $item['quantite'];
            }

            $vente = Vente::create([
                'total' => $total,
                'user_id' => $request->user()->id,
                'ordonnance_id' => $ordonnanceId
            ]);

            foreach ($request->items as $item) {
                $medicament = Medicament::findOrFail($item['medicament_id']);

                DetailVente::create([
                    'vente_id' => $vente->id,
                    'medicament_id' => $medicament->id,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $medicament->prix
                ]);

                $medicament->decrement('stock', $item['quantite']);

                // Record stock movement
                MouvementStock::create([
                    'medicament_id' => $medicament->id,
                    'quantite' => -$item['quantite'],
                    'type' => 'vente',
                    'motif' => 'Vente #' . $vente->id,
                    'user_id' => $request->user()->id
                ]);
            }

            return $vente->load(['details.medicament', 'ordonnance']);
        });
    }

    public function show(Vente $vente)
    {
        return $vente->load(['user', 'details.medicament', 'ordonnance']);
    }
}
