<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Medicament;
use App\Models\Vente;
use App\Models\DetailVente;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    // Liste des commandes (vendeur voit ses commandes, admin voit tout)
    public function index(Request $request)
    {
        $query = Commande::with(['vendeur', 'details.medicament', 'patient', 'ordonnance']);

        // Si vendeur, ne voir que ses commandes
        if ($request->user()->role === 'vendeur') {
            $query->where('vendeur_id', $request->user()->id);
        }

        return $query->latest()->get();
    }

    // Commandes en attente (pour caissier)
    public function pending()
    {
        return Commande::with(['vendeur', 'details.medicament', 'patient'])
            ->where('statut', 'en_attente')
            ->latest()
            ->get();
    }

    // Créer une commande (vendeur)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.medicament_id' => 'required|exists:medicaments,id',
            'items.*.quantite' => 'required|integer|min:1',
            'patient_id' => 'nullable|exists:patients,id',
            'ordonnance_id' => 'nullable|exists:ordonnances,id',
            'notes' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $validated) {
            // Calculer le total et vérifier le stock
            $total = 0;
            foreach ($validated['items'] as $item) {
                $medicament = Medicament::findOrFail($item['medicament_id']);

                // Vérifier stock disponible
                if ($medicament->stock < $item['quantite']) {
                    throw new \Exception("Stock insuffisant pour {$medicament->nom}. Disponible: {$medicament->stock}");
                }

                // Vérifier ordonnance si requise
                if ($medicament->ordonnance_requise && !$validated['ordonnance_id']) {
                    throw new \Exception("Ordonnance requise pour {$medicament->nom}");
                }

                $total += $medicament->prix * $item['quantite'];
            }

            // Créer la commande
            $commande = Commande::create([
                'numero_ticket' => Commande::generateTicketNumber(),
                'vendeur_id' => $request->user()->id,
                'total' => $total,
                'statut' => 'en_attente',
                'patient_id' => $validated['patient_id'] ?? null,
                'ordonnance_id' => $validated['ordonnance_id'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]);

            // Créer les détails
            foreach ($validated['items'] as $item) {
                $medicament = Medicament::findOrFail($item['medicament_id']);

                DetailCommande::create([
                    'commande_id' => $commande->id,
                    'medicament_id' => $medicament->id,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $medicament->prix
                ]);
            }

            return $commande->load(['details.medicament', 'vendeur', 'patient']);
        });
    }

    // Détails d'une commande
    public function show(Commande $commande)
    {
        return $commande->load(['vendeur', 'details.medicament', 'patient', 'ordonnance', 'vente']);
    }

    // Encaisser et valider une commande (caissier)
    public function pay(Request $request, Commande $commande)
    {
        $validated = $request->validate([
            'mode_paiement' => 'required|in:especes,carte,mobile_money',
            'montant_recu' => 'required|numeric|min:0'
        ]);

        // Vérifier que la commande est en attente
        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette commande a déjà été traitée'
            ], 400);
        }

        // Vérifier que le montant reçu est suffisant
        if ($validated['montant_recu'] < $commande->total) {
            return response()->json([
                'message' => 'Montant insuffisant'
            ], 400);
        }

        return DB::transaction(function () use ($request, $commande, $validated) {
            // Re-vérifier le stock avant validation
            foreach ($commande->details as $detail) {
                $medicament = $detail->medicament;
                if ($medicament->stock < $detail->quantite) {
                    throw new \Exception("Stock insuffisant pour {$medicament->nom}. Disponible: {$medicament->stock}");
                }
            }

            // Créer la vente
            $vente = Vente::create([
                'total' => $commande->total,
                'user_id' => $request->user()->id,
                'commande_id' => $commande->id,
                'ordonnance_id' => $commande->ordonnance_id,
                'mode_paiement' => $validated['mode_paiement'],
                'montant_recu' => $validated['montant_recu'],
                'montant_rendu' => $validated['montant_recu'] - $commande->total
            ]);

            // Créer les détails de vente et décrémenter le stock
            foreach ($commande->details as $detail) {
                $medicament = $detail->medicament;

                // Créer détail vente
                DetailVente::create([
                    'vente_id' => $vente->id,
                    'medicament_id' => $medicament->id,
                    'quantite' => $detail->quantite,
                    'prix_unitaire' => $detail->prix_unitaire
                ]);

                // Décrémenter le stock
                $medicament->decrement('stock', $detail->quantite);

                // Enregistrer mouvement de stock
                MouvementStock::create([
                    'medicament_id' => $medicament->id,
                    'quantite' => -$detail->quantite,
                    'type' => 'vente',
                    'motif' => "Vente #{$vente->id} - Commande #{$commande->numero_ticket}",
                    'user_id' => $request->user()->id
                ]);
            }

            // Mettre à jour le statut de la commande
            $commande->update(['statut' => 'payee']);

            return $vente->load(['details.medicament', 'commande', 'user']);
        });
    }

    // Annuler une commande (admin uniquement)
    public function cancel(Request $request, Commande $commande)
    {
        // Vérifier que l'utilisateur est admin
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier que la commande est en attente
        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Seules les commandes en attente peuvent être annulées'
            ], 400);
        }

        $commande->update(['statut' => 'annulee']);

        return response()->json([
            'message' => 'Commande annulée avec succès',
            'commande' => $commande
        ]);
    }
}
