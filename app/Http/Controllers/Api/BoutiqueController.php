<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoutiqueController extends Controller
{
    /** GET /api/boutiques — liste des boutiques de l'utilisateur (admin ou vendeur) */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            $boutiques = Boutique::with(['activites', 'parametres'])
                ->where('admin_id', $user->id)
                ->where('actif', true)
                ->get();
        } else {
            $boutiques = $user->boutiques()
                ->with(['activites', 'parametres'])
                ->where('boutiques.actif', true)
                ->get();
        }

        return response()->json($boutiques);
    }

    /** POST /api/boutiques */
    public function store(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403, 'Action réservée aux administrateurs.');

        $data = $request->validate([
            'nom'           => 'required|string|max:150',
            'adresse'       => 'nullable|string|max:255',
            'activite_ids'  => 'nullable|array',
            'activite_ids.*' => 'integer|exists:activites,id',
        ]);

        $boutique = Boutique::create([
            'admin_id' => $request->user()->id,
            'nom'      => $data['nom'],
            'adresse'  => $data['adresse'] ?? null,
        ]);

        if (!empty($data['activite_ids'])) {
            $boutique->activites()->sync($data['activite_ids']);
        }

        return response()->json($boutique->load(['activites', 'parametres']), 201);
    }

    /** GET /api/boutiques/{boutique} */
    public function show(Request $request, Boutique $boutique)
    {
        $this->authorizeAdmin($request, $boutique);
        return response()->json($boutique->load(['activites', 'parametres', 'vendeurs']));
    }

    /** PUT /api/boutiques/{boutique} */
    public function update(Request $request, Boutique $boutique)
    {
        $this->authorizeAdmin($request, $boutique);

        $data = $request->validate([
            'nom'            => 'sometimes|string|max:150',
            'adresse'        => 'nullable|string|max:255',
            'actif'          => 'sometimes|boolean',
            'activite_ids'   => 'nullable|array',
            'activite_ids.*' => 'integer|exists:activites,id',
        ]);

        $boutique->update($data);

        if (array_key_exists('activite_ids', $data)) {
            $boutique->activites()->sync($data['activite_ids'] ?? []);
        }

        return response()->json($boutique->load(['activites', 'parametres']));
    }

    /** DELETE /api/boutiques/{boutique} — soft delete */
    public function destroy(Request $request, Boutique $boutique)
    {
        $this->authorizeAdmin($request, $boutique);
        $boutique->update(['actif' => false]);
        return response()->json(['message' => 'Boutique désactivée.']);
    }

    /** PUT /api/boutiques/{boutique}/parametres */
    public function updateParametres(Request $request, Boutique $boutique)
    {
        $this->authorizeAdmin($request, $boutique);

        $data = $request->validate([
            'heure_cloture'       => 'sometimes|date_format:H:i',
            'fond_caisse_initial' => 'sometimes|numeric|min:0',
            'blocage_ecart_actif' => 'sometimes|boolean',
            'seuil_blocage_ecart' => 'nullable|numeric|min:0',
            'seuil_alerte_ecart'  => 'nullable|numeric|min:0',
        ]);

        $parametres = $boutique->parametres()->updateOrCreate(
            ['boutique_id' => $boutique->id],
            $data
        );

        return response()->json([
            'message'    => 'Paramètres mis à jour.',
            'parametres' => $parametres,
        ]);
    }

    /** POST /api/boutiques/{boutique}/vendeurs — rattacher un vendeur */
    public function attachVendeur(Request $request, Boutique $boutique)
    {
        $this->authorizeAdmin($request, $boutique);

        $data = $request->validate([
            'vendeur_id'        => 'required|integer|exists:users,id',
            'date_rattachement' => 'required|date',
        ]);

        $boutique->vendeurs()->syncWithoutDetaching([
            $data['vendeur_id'] => ['date_rattachement' => $data['date_rattachement'], 'actif' => true],
        ]);

        return response()->json(['message' => 'Vendeur rattaché.']);
    }

    /** DELETE /api/boutiques/{boutique}/vendeurs/{vendeur} */
    public function detachVendeur(Request $request, Boutique $boutique, User $vendeur)
    {
        $this->authorizeAdmin($request, $boutique);

        $boutique->vendeurs()->updateExistingPivot($vendeur->id, ['actif' => false]);
        return response()->json(['message' => 'Vendeur détaché.']);
    }

    private function authorizeAdmin(Request $request, Boutique $boutique): void
    {
        abort_if($boutique->admin_id !== $request->user()->id, 403, 'Accès refusé.');
    }
}
