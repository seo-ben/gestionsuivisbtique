<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaiementBoutiquier;
use App\Models\User;
use Illuminate\Http\Request;

class PaiementBoutiquierController extends Controller
{
    /** GET /api/paiements?vendeur_id=X */
    public function index(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $query = PaiementBoutiquier::with(['vendeur:id,nom', 'initiePar:id,nom'])
            ->whereHas('vendeur', function ($q) use ($request) {
                // Filtrer uniquement les vendeurs des boutiques de cet admin
                $q->whereIn('id', function ($sub) use ($request) {
                    $sub->select('vendeur_id')->from('boutique_vendeur')
                        ->whereIn('boutique_id', function ($bq) use ($request) {
                            $bq->select('id')->from('boutiques')->where('admin_id', $request->user()->id);
                        });
                });
            })
            ->orderByDesc('date_paiement');

        if ($request->vendeur_id) {
            $query->where('vendeur_id', $request->vendeur_id);
        }

        return response()->json($query->get());
    }

    /** POST /api/paiements */
    public function store(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $data = $request->validate([
            'vendeur_id'    => 'required|integer|exists:users,id',
            'montant'       => 'required|numeric|min:1',
            'type'          => 'required|in:salaire,prime,remboursement,autre',
            'date_paiement' => 'required|date',
            'note'          => 'nullable|string|max:255',
        ]);

        $paiement = PaiementBoutiquier::create([
            ...$data,
            'initie_par' => $request->user()->id,
            'statut'     => 'en_attente',
        ]);

        return response()->json($paiement->load(['vendeur:id,nom']), 201);
    }

    /**
     * PUT /api/paiements/{paiement}/confirmer
     * Le vendeur confirme la réception (depuis son app).
     */
    public function confirmer(Request $request, PaiementBoutiquier $paiement)
    {
        abort_if($paiement->vendeur_id !== $request->user()->id, 403, 'Ce paiement ne vous concerne pas.');
        abort_if($paiement->statut === 'confirme', 422, 'Paiement déjà confirmé.');

        $paiement->update([
            'statut'           => 'confirme',
            'date_confirmation' => now(),
        ]);

        return response()->json($paiement->fresh());
    }

    /** GET /api/mes-paiements — paiements du vendeur connecté */
    public function mesPaiements(Request $request)
    {
        $paiements = PaiementBoutiquier::where('vendeur_id', $request->user()->id)
            ->orderByDesc('date_paiement')
            ->get();

        return response()->json($paiements);
    }
}
