<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutorisationController;
use App\Http\Controllers\Api\BoutiqueController;
use App\Http\Controllers\Api\ClotureCaisseController;
use App\Http\Controllers\Api\EmpruntController;
use App\Http\Controllers\Api\PaiementBoutiquierController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\ReapprovisionnementController;
use App\Http\Controllers\Api\VendeurController;
use App\Http\Controllers\Api\VenteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — GESP Backend
|--------------------------------------------------------------------------
*/

// ──────────────────────────────────────────────
// AUTH (public)
// ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ──────────────────────────────────────────────
// Routes protégées par Sanctum
// ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // ── BOUTIQUES (admin) ──────────────────────
    Route::apiResource('boutiques', BoutiqueController::class);
    Route::put('boutiques/{boutique}/parametres', [BoutiqueController::class, 'updateParametres']);
    Route::post('boutiques/{boutique}/vendeurs',             [BoutiqueController::class, 'attachVendeur']);
    Route::delete('boutiques/{boutique}/vendeurs/{vendeur}', [BoutiqueController::class, 'detachVendeur']);

    // ── PRODUITS ───────────────────────────────
    Route::apiResource('boutiques.produits', ProduitController::class)->shallow();

    // ── VENDEURS (admin) ───────────────────────
    Route::apiResource('vendeurs', VendeurController::class)->except(['show']);

    // ── VENTES ────────────────────────────────
    Route::get('boutiques/{boutique}/ventes', [VenteController::class, 'index']);
    Route::post('ventes/sync',                [VenteController::class, 'sync']);

    // ── AUTORISATIONS ─────────────────────────
    Route::get('boutiques/{boutique}/autorisations',          [AutorisationController::class, 'index']);
    Route::post('boutiques/{boutique}/autorisations',         [AutorisationController::class, 'store']);
    Route::delete('autorisations/{autorisation}/revoquer',    [AutorisationController::class, 'revoquer']);
    Route::get('mes-autorisations',                           [AutorisationController::class, 'mesAutorisations']);

    // ── REAPPROVISIONNEMENTS ──────────────────
    Route::get('boutiques/{boutique}/reapprovisionnements', [ReapprovisionnementController::class, 'index']);
    Route::post('reapprovisionnements/sync',                [ReapprovisionnementController::class, 'sync']);

    // ── CLOTURES DE CAISSE ────────────────────
    Route::get('boutiques/{boutique}/clotures',    [ClotureCaisseController::class, 'index']);
    Route::post('boutiques/{boutique}/clotures',   [ClotureCaisseController::class, 'store']);
    Route::put('clotures/{cloture}/solde-reel',    [ClotureCaisseController::class, 'enregistrerSoldeReel']);

    // ── PAIEMENTS BOUTIQUIERS ─────────────────
    Route::get('paiements',                         [PaiementBoutiquierController::class, 'index']);
    Route::post('paiements',                        [PaiementBoutiquierController::class, 'store']);
    Route::put('paiements/{paiement}/confirmer',    [PaiementBoutiquierController::class, 'confirmer']);
    Route::get('mes-paiements',                     [PaiementBoutiquierController::class, 'mesPaiements']);

    // ── EMPRUNTS ──────────────────────────────
    Route::apiResource('emprunts', EmpruntController::class)->only(['index', 'store', 'show']);
    Route::put('echeances/{echeance}/payer', [EmpruntController::class, 'payerEcheance']);

    // ── RAPPORTS & DASHBOARD ──────────────────
    Route::get('dashboard',                              [RapportController::class, 'dashboard']);
    Route::get('boutiques/{boutique}/rapports/journalier', [RapportController::class, 'journalier']);
    Route::get('boutiques/{boutique}/rapports/mensuel',    [RapportController::class, 'mensuel']);
    Route::get('vendeurs/{vendeur}/ecarts',               [RapportController::class, 'ecartVendeur']);
});
