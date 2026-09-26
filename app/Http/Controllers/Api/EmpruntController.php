<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emprunt;
use App\Models\EcheanceEmprunt;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmpruntController extends Controller
{
    /** GET /api/emprunts */
    public function index(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $emprunts = Emprunt::where('admin_id', $request->user()->id)
            ->with('echeances')
            ->orderByDesc('date_octroi')
            ->get();

        return response()->json($emprunts);
    }

    /** POST /api/emprunts */
    public function store(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $data = $request->validate([
            'organisme_preteur' => 'required|string|max:150',
            'capital_emprunte'  => 'required|numeric|min:1',
            'taux_interet'      => 'required|numeric|min:0',
            'date_octroi'       => 'required|date',
            'duree_mois'        => 'required|integer|min:1',
            'note'              => 'nullable|string|max:255',
        ]);

        $emprunt = Emprunt::create([
            ...$data,
            'admin_id'          => $request->user()->id,
            'capital_restant_du' => $data['capital_emprunte'], // au départ = capital total
            'statut'            => 'en_cours',
        ]);

        // Générer automatiquement l'échéancier (mensualités constantes simplifiées)
        $this->genererEcheancier($emprunt);

        return response()->json($emprunt->load('echeances'), 201);
    }

    /** GET /api/emprunts/{emprunt} */
    public function show(Request $request, Emprunt $emprunt)
    {
        abort_if($emprunt->admin_id !== $request->user()->id, 403);
        return response()->json($emprunt->load('echeances'));
    }

    /**
     * PUT /api/echeances/{echeance}/payer
     * Enregistrer le paiement d'une échéance.
     */
    public function payerEcheance(Request $request, EcheanceEmprunt $echeance)
    {
        $emprunt = $echeance->emprunt;
        abort_if($emprunt->admin_id !== $request->user()->id, 403);
        abort_if($echeance->statut === 'payee', 422, 'Échéance déjà payée.');

        $data = $request->validate([
            'montant_paye'  => 'required|numeric|min:0.01',
            'date_paiement' => 'required|date',
        ]);

        $echeance->update([
            'montant_paye'  => $data['montant_paye'],
            'date_paiement' => $data['date_paiement'],
            'statut'        => 'payee',
        ]);

        // Mettre à jour le capital restant dû
        $emprunt->recalculerCapital();

        return response()->json([
            'echeance' => $echeance->fresh(),
            'emprunt'  => $emprunt->fresh(),
        ]);
    }

    /**
     * Génère l'échéancier avec mensualités constantes (méthode simplifiée).
     * Pour des amortissements complexes, étendre cette méthode.
     */
    private function genererEcheancier(Emprunt $emprunt): void
    {
        $mensualite  = round($emprunt->capital_emprunte / $emprunt->duree_mois, 2);
        $dateEcheance = Carbon::parse($emprunt->date_octroi)->addMonth();

        $echeances = [];
        for ($i = 1; $i <= $emprunt->duree_mois; $i++) {
            $echeances[] = [
                'emprunt_id'      => $emprunt->id,
                'numero_echeance' => $i,
                'date_echeance'   => $dateEcheance->toDateString(),
                'montant_prevu'   => $mensualite,
                'montant_paye'    => 0,
                'statut'          => 'a_venir',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
            $dateEcheance->addMonth();
        }

        EcheanceEmprunt::insert($echeances);
    }
}
