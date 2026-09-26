<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    /** GET /api/boutiques/{boutique}/produits */
    public function index(Request $request, Boutique $boutique)
    {
        $this->checkAccess($request, $boutique);

        $produits = $boutique->produits()
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        return response()->json($produits);
    }

    /** POST /api/boutiques/{boutique}/produits */
    public function store(Request $request, Boutique $boutique)
    {
        $this->checkAdmin($request, $boutique);

        $data = $request->validate([
            'nom'                       => 'required|string|max:150',
            'prix_actuel'               => 'required|numeric|min:0',
            'unite_reference'           => 'nullable|string|max:30',
            'vendeur_peut_modifier_prix' => 'boolean',
            'stock_actuel'              => 'numeric|min:0',
            'seuil_alerte_stock'        => 'nullable|numeric|min:0',
        ]);

        $produit = $boutique->produits()->create($data);

        return response()->json($produit, 201);
    }

    /** GET /api/produits/{produit} */
    public function show(Request $request, Produit $produit)
    {
        $this->checkAccess($request, $produit->boutique);
        return response()->json($produit);
    }

    /** PUT /api/produits/{produit} */
    public function update(Request $request, Produit $produit)
    {
        $this->checkAdmin($request, $produit->boutique);

        $data = $request->validate([
            'nom'                       => 'sometimes|string|max:150',
            'prix_actuel'               => 'sometimes|numeric|min:0',
            'unite_reference'           => 'nullable|string|max:30',
            'vendeur_peut_modifier_prix' => 'sometimes|boolean',
            'seuil_alerte_stock'        => 'nullable|numeric|min:0',
            'actif'                     => 'sometimes|boolean',
        ]);

        $produit->update($data);
        return response()->json($produit->fresh());
    }

    /** DELETE /api/produits/{produit} — soft delete */
    public function destroy(Request $request, Produit $produit)
    {
        $this->checkAdmin($request, $produit->boutique);
        $produit->update(['actif' => false]);
        return response()->json(['message' => 'Produit désactivé.']);
    }
}
