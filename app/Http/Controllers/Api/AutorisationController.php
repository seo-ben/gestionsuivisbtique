<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutorisationReapprovisionnement;
use App\Models\Boutique;
use Illuminate\Http\Request;

class AutorisationController extends Controller
{
    /** GET /api/boutiques/{boutique}/autorisations */
    public function index(Request $request, Boutique $boutique)
    {
        $this->checkAdmin($request, $boutique);

        $autorisations = $boutique->autorisations()
            ->with(['vendeur:id,nom,telephone'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($autorisations);
    }

    /**
     * GET /api/mes-autorisations
     * Retourne les autorisations actives du vendeur connecté (pour l'app mobile).
     */
    public function mesAutorisations(Request $request)
    {
        $autorisations = AutorisationReapprovisionnement::where('vendeur_id', $request->user()->id)
            ->where('statut', 'active')
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>', now()))
            ->with(['boutique:id,nom', 'accordeePar:id,nom'])
            ->get();

        return response()->json($autorisations);
    }

    /** POST /api/boutiques/{boutique}/autorisations */
    public function store(Request $request, Boutique $boutique)
    {
        $this->checkAdmin($request, $boutique);

        $data = $request->validate([
            'vendeur_id'  => 'required|integer|exists:users,id',
            'type'        => 'required|in:ponctuelle,permanente',
            'date_debut'  => 'required|date',
            'date_fin'    => 'nullable|date|after:date_debut',
            'note'        => 'nullable|string|max:255',
        ]);

        // Vérifier que le vendeur est rattaché à cette boutique
        abort_if(
            !$boutique->vendeurs()->where('users.id', $data['vendeur_id'])->exists(),
            422,
            'Ce vendeur n\'est pas rattaché à cette boutique.'
        );

        $autorisation = $boutique->autorisations()->create([
            ...$data,
            'accordee_par' => $request->user()->id,
            'statut'       => 'active',
        ]);

        return response()->json($autorisation->load('vendeur:id,nom'), 201);
    }

    /** DELETE /api/autorisations/{autorisation} — révoquer */
    public function revoquer(Request $request, AutorisationReapprovisionnement $autorisation)
    {
        // Vérifier que l'admin est propriétaire de la boutique
        $boutique = $autorisation->boutique;
        abort_if($boutique->admin_id !== $request->user()->id, 403);
        abort_if(!in_array($autorisation->statut, ['active']), 422, 'Seule une autorisation active peut être révoquée.');

        $autorisation->update(['statut' => 'revoquee']);
        return response()->json(['message' => 'Autorisation révoquée.']);
    }
}
