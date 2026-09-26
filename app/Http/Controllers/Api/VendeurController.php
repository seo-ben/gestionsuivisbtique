<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VendeurController extends Controller
{
    /** GET /api/vendeurs — liste des vendeurs créés par l'admin */
    public function index(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        // Tous les vendeurs rattachés aux boutiques de cet admin
        $vendeurs = User::where('role', 'vendeur')
            ->whereHas('boutiques', function ($q) use ($request) {
                $q->where('boutiques.admin_id', $request->user()->id);
            })
            ->get(['id', 'uuid', 'nom', 'telephone', 'email', 'actif']);

        return response()->json($vendeurs);
    }

    /** POST /api/vendeurs — créer un compte vendeur */
    public function store(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $data = $request->validate([
            'nom'       => 'required|string|max:150',
            'telephone' => 'required|string|max:30|unique:users,telephone',
            'email'     => 'nullable|email|unique:users,email',
            'password'  => 'required|string|min:6',
        ]);

        $vendeur = User::create([
            'nom'       => $data['nom'],
            'telephone' => $data['telephone'],
            'email'     => $data['email'] ?? null,
            'password'  => Hash::make($data['password']),
            'role'      => 'vendeur',
            'actif'     => true,
        ]);

        return response()->json($vendeur->only(['id', 'uuid', 'nom', 'telephone', 'role']), 201);
    }

    /** PUT /api/vendeurs/{vendeur} */
    public function update(Request $request, User $vendeur)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        abort_if($vendeur->role !== 'vendeur', 422, 'Cet utilisateur n\'est pas un vendeur.');

        $data = $request->validate([
            'nom'       => 'sometimes|string|max:150',
            'telephone' => 'sometimes|string|max:30|unique:users,telephone,' . $vendeur->id,
            'email'     => 'nullable|email|unique:users,email,' . $vendeur->id,
            'password'  => 'nullable|string|min:6',
            'actif'     => 'sometimes|boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $vendeur->update($data);

        return response()->json($vendeur->fresh()->only(['id', 'uuid', 'nom', 'telephone', 'actif']));
    }

    /** DELETE /api/vendeurs/{vendeur} — désactiver uniquement */
    public function destroy(Request $request, User $vendeur)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $vendeur->update(['actif' => false]);
        // Révoquer tous les tokens
        $vendeur->tokens()->delete();
        return response()->json(['message' => 'Compte vendeur désactivé.']);
    }
}
