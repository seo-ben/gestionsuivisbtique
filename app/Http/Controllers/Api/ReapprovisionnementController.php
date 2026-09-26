<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Services\SyncService;
use Illuminate\Http\Request;

class ReapprovisionnementController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    /** GET /api/boutiques/{boutique}/reapprovisionnements?date=2026-09-26 */
    public function index(Request $request, Boutique $boutique)
    {
        $this->checkAccess($request, $boutique);

        $date   = $request->query('date', now()->toDateString());
        $reappros = $boutique->reapprovisionnements()
            ->with(['produit:id,nom', 'vendeur:id,nom', 'autorisation:id,type'])
            ->whereDate('date_reappro', $date)
            ->orderBy('session_uuid')
            ->orderBy('heure_reappro')
            ->get();

        return response()->json($reappros);
    }

    /**
     * POST /api/reapprovisionnements/sync
     * Synchronise un lot de réapprovisionnements hors-ligne.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'reapprovisionnements'   => 'required|array|min:1',
            'reapprovisionnements.*.uuid'           => 'required|uuid',
            'reapprovisionnements.*.session_uuid'   => 'nullable|uuid',
            'reapprovisionnements.*.autorisation_id' => 'required|integer|exists:autorisations_reapprovisionnement,id',
            'reapprovisionnements.*.boutique_id'    => 'required|integer|exists:boutiques,id',
            'reapprovisionnements.*.produit_id'     => 'required|integer|exists:produits,id',
            'reapprovisionnements.*.quantite'       => 'required|numeric|min:0.001',
            'reapprovisionnements.*.unite'          => 'required|string|max:30',
            'reapprovisionnements.*.montant_depense' => 'required|numeric|min:0',
            'reapprovisionnements.*.fournisseur'    => 'nullable|string|max:150',
            'reapprovisionnements.*.date_reappro'   => 'required|date_format:Y-m-d',
            'reapprovisionnements.*.heure_reappro'  => 'required|date_format:H:i:s',
            'reapprovisionnements.*.created_at_local' => 'required|date',
            'reapprovisionnements.*.device_id'      => 'nullable|string|max:100',
        ]);

        $result = $this->syncService->syncReapprovisionnements(
            $request->reapprovisionnements,
            $request->user()->id
        );

        return response()->json([
            'message' => "Synchronisation terminée.",
            ...$result,
        ]);
    }
}
