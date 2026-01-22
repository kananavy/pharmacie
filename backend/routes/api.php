<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\MedicamentController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VenteController;
use App\Http\Controllers\Api\LotController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ClotureCaisseController;
use App\Http\Controllers\Api\AuditLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::post('/register', [AuthController::class, 'register']); // Disabled: User management is admin-only now
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Settings Routes
    Route::get('settings', [SettingController::class, 'index']);
    Route::post('settings', [SettingController::class, 'update']);

    // Cash Closing Routes
    Route::get('cash-closing/current', [ClotureCaisseController::class, 'showCurrent']);
    Route::post('cash-closing', [ClotureCaisseController::class, 'store']);

    // Audit Logs Routes
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);

    Route::apiResource('lots', LotController::class)->only(['store']);

    Route::apiResource('medicaments', MedicamentController::class);
    Route::get('medicaments/alerts/all', [MedicamentController::class, 'alerts']);

    Route::apiResource('ventes', VenteController::class)->only(['index', 'store', 'show']);
    Route::post('ventes/{vente}/cancel', [VenteController::class, 'cancelSale']);
    Route::post('ventes/{vente}/return-partial', [VenteController::class, 'returnPartial']);

    Route::apiResource('fournisseurs', FournisseurController::class);

    Route::apiResource('patients', PatientController::class);

    // Commandes (Two-step sales workflow)
    Route::apiResource('commandes', CommandeController::class)->only(['index', 'store', 'show']);
    Route::get('commandes/pending/all', [CommandeController::class, 'pending']);
    Route::post('commandes/{commande}/pay', [CommandeController::class, 'pay']);
    Route::post('commandes/{commande}/cancel', [CommandeController::class, 'cancel']);

    Route::get('reports/daily', [ReportController::class, 'daily']);
    Route::get('reports/monthly', [ReportController::class, 'monthly']);

    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
});
