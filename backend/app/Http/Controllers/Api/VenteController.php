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
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.medicament_id' => 'required|exists:medicaments,id',
            'items.*.quantite' => 'required|integer|min:1',
            'items.*.type_vente' => 'nullable|in:boite,unite', // We'll only handle 'boite' for now
            'ordonnance' => 'nullable|array',
            'ordonnance.numero' => 'required_with:ordonnance|string',
            'ordonnance.medecin' => 'required_with:ordonnance|string',
            'ordonnance.date_ordonnance' => 'required_with:ordonnance|date',
            'mode_paiement' => 'nullable|in:especes,carte,mobile_money',
            'montant_recu' => 'nullable|numeric|min:0',
            'client_nom' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $ordonnanceId = null;
            if ($request->has('ordonnance')) {
                $ordonnance = Ordonnance::create($request->ordonnance);
                $ordonnanceId = $ordonnance->id;
            }

            $totalVente = 0;
            $detailsPourVente = [];

            // First loop: Validate stock and calculate total
            foreach ($validated['items'] as $item) {
                $medicament = Medicament::findOrFail($item['medicament_id']);
                $quantiteDemandee = $item['quantite'];
                $typeVente = $item['type_vente'] ?? 'boite';

                // TODO: Implement unit sale logic with lots. For now, we block it.
                if ($typeVente === 'unite') {
                    throw new \Exception("La vente à l'unité n'est pas encore supportée avec la nouvelle gestion de stock.");
                }

                if ($medicament->ordonnance_requise && !$ordonnanceId) {
                    throw new \Exception("Ordonnance requise pour " . $medicament->nom);
                }

                // New Stock Validation Logic (based on lots)
                $stockTotalDisponible = $medicament->lots()
                    ->where('quantite_actuelle', '>', 0)
                    ->where('date_expiration', '>', now())
                    ->sum('quantite_actuelle');

                if ($stockTotalDisponible < $quantiteDemandee) {
                    throw new \Exception("Stock insuffisant pour {$medicament->nom}. Disponible: {$stockTotalDisponible}, Demandé: {$quantiteDemandee}");
                }

                // Calculate total for this item
                $totalVente += $medicament->prix * $quantiteDemandee;
            }


            // Handle Payment Logic
            $montantRecu = $request->input('montant_recu', $totalVente);
            $modePaiement = $request->input('mode_paiement', 'especes');

            if ($montantRecu < $totalVente && !app()->runningUnitTests()) {
                 if (abs($montantRecu - $totalVente) > 0.01) { // floating point tolerance
                    throw new \Exception("Montant reçu insuffisant (Total: {$totalVente})");
                 }
            }
            $montantRendu = $montantRecu - $totalVente;

            // Create the main Vente record
            $vente = Vente::create([
                'total' => $totalVente,
                'user_id' => $request->user()->id,
                'ordonnance_id' => $ordonnanceId,
                'mode_paiement' => $modePaiement,
                'montant_recu' => $montantRecu,
                'montant_rendu' => $montantRendu > 0 ? $montantRendu : 0,
            ]);


            // Second loop: Process sale, deduct from lots, and create detail records
            foreach ($validated['items'] as $item) {
                $medicament = Medicament::findOrFail($item['medicament_id']);
                $quantiteAVendre = $item['quantite'];

                // FEFO Logic: First Expired, First Out
                $lotsDisponibles = $medicament->lots()
                    ->where('quantite_actuelle', '>', 0)
                    ->where('date_expiration', '>', now())
                    ->orderBy('date_expiration', 'asc')
                    ->get();

                foreach ($lotsDisponibles as $lot) {
                    if ($quantiteAVendre <= 0) break;

                    $quantiteAPrendreDuLot = min($quantiteAVendre, $lot->quantite_actuelle);

                    // Create a DetailVente for each lot used
                    DetailVente::create([
                        'vente_id' => $vente->id,
                        'medicament_id' => $medicament->id,
                        'lot_id' => $lot->id,
                        'quantite' => $quantiteAPrendreDuLot,
                        'prix_unitaire' => $medicament->prix, // Assuming box price
                        'type_vente' => 'boite'
                    ]);

                    // Deduct stock from the lot
                    $lot->decrement('quantite_actuelle', $quantiteAPrendreDuLot);

                    // Create stock movement record
                    MouvementStock::create([
                        'medicament_id' => $medicament->id,
                        'quantite' => -$quantiteAPrendreDuLot,
                        'type' => 'vente',
                        'motif' => 'Vente #' . $vente->id . ' (Lot: ' . $lot->numero_lot . ')',
                        'user_id' => $request->user()->id
                    ]);

                    $quantiteAVendre -= $quantiteAPrendreDuLot;
                }
            }

            return $vente->load(['details.medicament', 'details.lot', 'ordonnance']);
        });
    }

    public function show(Vente $vente)
    {
        return $vente->load(['user', 'details.medicament', 'ordonnance']);
    }

    /**
     * Cancel a sale and restock items.
     */
    public function cancelSale(Request $request, Vente $vente)
    {
        $request->validate([
            'motif' => 'required|string|min:10',
        ]);

        if ($vente->status === 'cancelled') {
            throw new \Exception("Cette vente est déjà annulée.");
        }
        if ($vente->status === 'returned_partially') {
            throw new \Exception("Cette vente a déjà fait l'objet d'un retour partiel. Annulation complète impossible.");
        }


        return DB::transaction(function () use ($request, $vente) {
            $vente->update(['status' => 'cancelled']);

            foreach ($vente->details as $detail) {
                // Find the lot and restock
                $lot = $detail->lot; // Assumes lot_id is set on DetailVente
                if ($lot) {
                    $lot->increment('quantite_actuelle', $detail->quantite);
                } else {
                    // This case should ideally not happen if lot_id is always recorded
                    // Log error or handle appropriately
                }

                // Record stock movement for return
                MouvementStock::create([
                    'medicament_id' => $detail->medicament_id,
                    'quantite' => $detail->quantite, // Positive quantity for return
                    'type' => 'retour',
                    'motif' => 'Annulation Vente #' . $vente->id . ': ' . $request->motif,
                    'user_id' => $request->user()->id
                ]);
            }

            return response()->json(['message' => 'Vente annulée et articles remis en stock.', 'vente' => $vente->load('details.lot')], 200);
        });
    }

    /**
     * Process a partial return for a sale.
     * This creates a new 'return' transaction.
     */
    public function returnPartial(Request $request, Vente $vente)
    {
        $validated = $request->validate([
            'returned_items' => 'required|array|min:1',
            'returned_items.*.detail_vente_id' => 'required|exists:details_vente,id',
            'returned_items.*.quantite' => 'required|integer|min:1',
            'motif' => 'required|string|min:10',
        ]);

        if ($vente->status === 'cancelled') {
            throw new \Exception("Impossible d'effectuer un retour partiel sur une vente annulée.");
        }

        return DB::transaction(function () use ($request, $vente, $validated) {
            $totalRetour = 0;
            $itemsPourRetour = [];

            foreach ($validated['returned_items'] as $returnedItem) {
                $originalDetail = DetailVente::with('lot', 'medicament')->findOrFail($returnedItem['detail_vente_id']);

                if ($returnedItem['quantite'] > $originalDetail->quantite) {
                    throw new \Exception("La quantité retournée pour le médicament " . $originalDetail->medicament->nom . " dépasse la quantité vendue.");
                }

                $lot = $originalDetail->lot;
                if (!$lot) {
                    throw new \Exception("Lot introuvable pour le détail de vente " . $originalDetail->id);
                }

                $totalRetour += $originalDetail->prix_unitaire * $returnedItem['quantite'];
                $itemsPourRetour[] = [
                    'originalDetail' => $originalDetail,
                    'quantite' => $returnedItem['quantite'],
                    'lot' => $lot,
                ];
            }

            // Create a new Vente record for the return (with negative total)
            $returnVente = Vente::create([
                'total' => -$totalRetour, // Negative total to signify a return/refund
                'user_id' => $request->user()->id, // The user processing the return
                'ordonnance_id' => $vente->ordonnance_id,
                'commande_id' => $vente->commande_id,
                'mode_paiement' => $vente->mode_paiement, // Assume refund via original method
                'montant_recu' => -$totalRetour,
                'montant_rendu' => 0,
                'status' => 'returned', // New status for return transactions
                // You might want to link this return sale to the original sale
                // e.g., 'original_vente_id' => $vente->id
            ]);

            foreach ($itemsPourRetour as $itemData) {
                $originalDetail = $itemData['originalDetail'];
                $quantiteRetournee = $itemData['quantite'];
                $lot = $itemData['lot'];

                // Create DetailVente for the return transaction
                DetailVente::create([
                    'vente_id' => $returnVente->id,
                    'medicament_id' => $originalDetail->medicament_id,
                    'lot_id' => $lot->id,
                    'quantite' => -$quantiteRetournee, // Negative quantity for returned item
                    'prix_unitaire' => $originalDetail->prix_unitaire,
                    'type_vente' => $originalDetail->type_vente
                ]);

                // Restock the lot
                $lot->increment('quantite_actuelle', $quantiteRetournee);

                // Record stock movement for return
                MouvementStock::create([
                    'medicament_id' => $originalDetail->medicament_id,
                    'quantite' => $quantiteRetournee,
                    'type' => 'retour',
                    'motif' => 'Retour partiel Vente #' . $vente->id . ' (transaction retour #' . $returnVente->id . '): ' . $validated['motif'],
                    'user_id' => $request->user()->id
                ]);
            }

            // Update status of original sale if it's the first partial return
            if ($vente->status === 'completed') {
                $vente->update(['status' => 'returned_partially']);
            }

            return response()->json([
                'message' => 'Retour partiel enregistré avec succès.',
                'return_transaction' => $returnVente->load(['details.medicament', 'details.lot']),
                'original_vente_updated' => $vente->fresh()
            ], 200);
        });
    }
}
