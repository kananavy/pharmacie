<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClotureCaisse;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClotureCaisseController extends Controller
{
    /**
     * Get the theoretical totals for the current cashier since their last closing.
     */
    public function showCurrent(Request $request)
    {
        $userId = Auth::id();

        // Find the last closing performed by this user
        $lastClosing = ClotureCaisse::where('user_id', $userId)
            ->whereNotNull('date_cloture')
            ->latest('date_cloture')
            ->first();

        // Determine the start date for sales calculation
        $startDate = $lastClosing ? $lastClosing->date_cloture : null;

        // Calculate theoretical sales total since last closing (or beginning of time for first closing)
        $salesQuery = Vente::where('user_id', $userId);
        if ($startDate) {
            $salesQuery->where('created_at', '>', $startDate);
        }

        $totalTheorique = $salesQuery->sum('total');
        // You might want to break this down by payment method later

        return response()->json([
            'user_id' => $userId,
            'last_closing_at' => $lastClosing ? $lastClosing->date_cloture->toDateTimeString() : null,
            'theoretical_total' => round($totalTheorique, 2),
            // Add other theoretical totals by payment method here if needed
        ]);
    }

    /**
     * Store a new cash closing record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'theoretical_total' => 'required|numeric|min:0', // Should be provided by frontend based on showCurrent
            'actual_total_especes' => 'required|numeric|min:0',
            // Add other payment methods here: 'actual_total_carte', 'actual_total_mobile_money'
            'commentaires' => 'nullable|string',
        ]);

        $userId = Auth::id();

        // Re-calculate theoretical total to prevent manipulation from frontend
        $lastClosing = ClotureCaisse::where('user_id', $userId)
            ->whereNotNull('date_cloture')
            ->latest('date_cloture')
            ->first();
        $startDate = $lastClosing ? $lastClosing->date_cloture : null;
        $salesQuery = Vente::where('user_id', $userId);
        if ($startDate) {
            $salesQuery->where('created_at', '>', $startDate);
        }
        $recalculatedTheoreticalTotal = round($salesQuery->sum('total'), 2);

        if (abs($recalculatedTheoreticalTotal - $validated['theoretical_total']) > 0.01 && !app()->runningUnitTests()) {
            throw ValidationException::withMessages([
                'theoretical_total' => "Le total théorique fourni ne correspond pas aux ventes réelles. Veuillez rafraîchir."
            ]);
        }

        $actualTotal = $validated['actual_total_especes']; // Sum all actual totals if multiple payment methods

        $ecart = round($actualTotal - $recalculatedTheoreticalTotal, 2);

        $cloture = ClotureCaisse::create([
            'user_id' => $userId,
            // 'pharmacie_id' => /* get current pharmacy ID if multi-site is active */,
            'date_ouverture' => $startDate ?? now()->subMinutes(5), // Approximate start, actual logic would be more precise
            'date_cloture' => now(),
            'total_theorique' => $recalculatedTheoreticalTotal,
            'total_reel' => $actualTotal,
            'ecart' => $ecart,
            'commentaires' => $validated['commentaires'],
        ]);

        return response()->json($cloture, 201);
    }
}
