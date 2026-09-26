<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Vente;
use App\Services\SyncService;
use Illuminate\Http\Request;

class VenteController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    /** GET /api/boutiques/{boutique}/ventes?date=2026-09-26 */
    public function index(Request $request, Boutique $boutique)
    {
        $this->checkAccess($request, $boutique);

        $date   = $request->query('date', now()->toDateString());
        $ventes = $boutique->ventes()
            ->with(['produit:id,nom,unite_reference', 'vendeur:id,nom'])
            ->whereDate('date_vente', $date)
            ->orderBy('heure_vente')
            ->get();

        return response()->json($ventes);
    }

    /**
     * POST /api/ventes/sync
     * Reçoit un lot de ventes hors-ligne et les synchronise.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'ventes'          => 'required|array|min:1',
            'ventes.*.uuid'   => 'required|uuid',
            'ventes.*.boutique_id'    => 'required|integer|exists:boutiques,id',
            'ventes.*.produit_id'     => 'required|integer|exists:produits,id',
            'ventes.*.quantite'       => 'required|numeric|min:0.001',
            'ventes.*.unite'          => 'required|string|max:30',
            'ventes.*.prix_unitaire'  => 'required|numeric|min:0',
            'ventes.*.montant_total'  => 'required|numeric|min:0',
            'ventes.*.date_vente'     => 'required|date_format:Y-m-d',
            'ventes.*.heure_vente'    => 'required|date_format:H:i:s',
            'ventes.*.created_at_local' => 'required|date',
            'ventes.*.device_id'      => 'nullable|string|max:100',
        ]);

        $result = $this->syncService->syncVentes(
            $request->ventes,
            $request->user()->id
        );

        return response()->json([
            'message' => "Synchronisation terminée.",
            ...$result,
        ]);
    }
}
