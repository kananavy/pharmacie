<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\MedicamentController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VenteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('medicaments', MedicamentController::class);
    Route::post('medicaments/{medicament}/ajuster', [MedicamentController::class, 'ajusterStock']);
    Route::get('medicaments/alerts/all', [MedicamentController::class, 'alerts']);

    Route::apiResource('ventes', VenteController::class)->only(['index', 'store', 'show']);

    Route::apiResource('fournisseurs', FournisseurController::class);

    Route::apiResource('patients', PatientController::class);

    // Commandes (Two-step sales workflow)
    Route::apiResource('commandes', CommandeController::class)->only(['index', 'store', 'show']);
    Route::get('commandes/pending/all', [CommandeController::class, 'pending']);
    Route::post('commandes/{commande}/pay', [CommandeController::class, 'pay']);
    Route::post('commandes/{commande}/cancel', [CommandeController::class, 'cancel']);

    Route::get('reports/daily', [ReportController::class, 'daily']);
    Route::get('reports/monthly', [ReportController::class, 'monthly']);
});
