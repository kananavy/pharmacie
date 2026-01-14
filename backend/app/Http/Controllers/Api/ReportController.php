<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\Vente;
use App\Models\DetailVente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily()
    {
        $today = now()->toDateString();

        $salesCount = Vente::whereDate('created_at', $today)->count();
        $totalRevenue = Vente::whereDate('created_at', $today)->sum('total');

        $stockValuation = Medicament::select(DB::raw('SUM(stock * prix_achat) as total_val'))->first()->total_val ?? 0;

        $topMedications = DetailVente::select('medicament_id', DB::raw('SUM(quantite) as total_qty'))
            ->whereDate('created_at', $today)
            ->groupBy('medicament_id')
            ->orderByDesc('total_qty')
            ->with('medicament')
            ->take(5)
            ->get();

        return response()->json([
            'date' => $today,
            'sales_count' => $salesCount,
            'total_revenue' => $totalRevenue,
            'stock_valuation' => $stockValuation,
            'top_medications' => $topMedications
        ]);
    }

    public function monthly()
    {
        $startOfMonth = now()->startOfMonth();

        $dailyRevenue = Vente::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'))
            ->where('created_at', '>=', $startOfMonth)
            ->groupBy('date')
            ->get();

        return response()->json([
            'month' => now()->format('F Y'),
            'total_revenue' => $dailyRevenue->sum('revenue'),
            'daily_stats' => $dailyRevenue
        ]);
    }
}
