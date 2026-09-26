<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use Illuminate\Http\Request;

class DepenseController extends Controller
{
    /**
     * GET /api/depenses
     * Liste des dépenses (avec filtres optionnels : boutique_id, debut, fin, date, categorie).
     */
    public function index(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403, 'Accès réservé aux administrateurs.');

        $query = Depense::where('admin_id', $request->user()->id)
            ->with(['boutique:id,nom'])
            ->orderByDesc('date_depense')
            ->orderByDesc('id');

        if ($request->filled('boutique_id')) {
            $boutiqueId = $request->query('boutique_id');
            if ($boutiqueId === 'null' || $boutiqueId === '0') {
                $query->whereNull('boutique_id');
            } else {
                $query->where('boutique_id', (int) $boutiqueId);
            }
        }

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->query('categorie'));
        }

        if ($request->filled('debut') && $request->filled('fin')) {
            $query->whereBetween('date_depense', [$request->query('debut'), $request->query('fin')]);
        } elseif ($request->filled('date')) {
            $query->whereDate('date_depense', $request->query('date'));
        }

        return response()->json($query->get());
    }

    /**
     * POST /api/depenses
     * Enregistrer une dépense pour une boutique ou en dépense générale.
     */
    public function store(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403, 'Accès réservé aux administrateurs.');

        $data = $request->validate([
            'boutique_id'       => 'nullable|integer|exists:boutiques,id',
            'categorie'         => 'required|string|max:50',
            'libelle'           => 'required|string|max:150',
            'montant'           => 'required|numeric|min:1',
            'date_depense'      => 'required|date',
            'deduire_de_caisse' => 'nullable|boolean',
            'note'              => 'nullable|string|max:500',
        ]);

        $depense = Depense::create([
            'admin_id'          => $request->user()->id,
            'boutique_id'       => $data['boutique_id'] ?? null,
            'categorie'         => $data['categorie'],
            'libelle'           => $data['libelle'],
            'montant'           => $data['montant'],
            'date_depense'      => $data['date_depense'],
            'deduire_de_caisse' => $data['deduire_de_caisse'] ?? true,
            'note'              => $data['note'] ?? null,
        ]);

        return response()->json($depense->load('boutique:id,nom'), 201);
    }

    /**
     * DELETE /api/depenses/{depense}
     */
    public function destroy(Request $request, Depense $depense)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        abort_if($depense->admin_id !== $request->user()->id, 403);

        $depense->delete();

        return response()->json(['message' => 'Dépense supprimée avec succès.']);
    }
}
