<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    /**
     * GET /api/boutiques/{boutique}/rapports/journalier?date=2026-09-26
     * Rapport journalier complet d'une boutique.
     */
    public function journalier(Request $request, Boutique $boutique)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        abort_if($boutique->admin_id !== $request->user()->id, 403);

        $date = $request->query('date', now()->toDateString());

        $totalVentes   = $boutique->ventes()->whereDate('date_vente', $date)->sum('montant_total');
        $totalSorties  = $boutique->reapprovisionnements()->whereDate('date_reappro', $date)->sum('montant_depense');
        $nbVentes      = $boutique->ventes()->whereDate('date_vente', $date)->count();

        // Ventes par vendeur
        $ventesParVendeur = $boutique->ventes()
            ->whereDate('date_vente', $date)
            ->join('users', 'users.id', '=', 'ventes.vendeur_id')
            ->groupBy('ventes.vendeur_id', 'users.nom')
            ->selectRaw('ventes.vendeur_id, users.nom as vendeur_nom, SUM(ventes.montant_total) as total, COUNT(*) as nb_ventes')
            ->get();

        // Ventes par produit
        $ventesParProduit = $boutique->ventes()
            ->whereDate('date_vente', $date)
            ->join('produits', 'produits.id', '=', 'ventes.produit_id')
            ->groupBy('ventes.produit_id', 'produits.nom')
            ->selectRaw('ventes.produit_id, produits.nom as produit_nom, SUM(ventes.quantite) as qte_vendue, SUM(ventes.montant_total) as total')
            ->get();

        // Clôture du jour si existante
        $cloture = $boutique->clotures()->whereDate('date_cloture', $date)->first();

        return response()->json(compact(
            'date', 'totalVentes', 'totalSorties', 'nbVentes',
            'ventesParVendeur', 'ventesParProduit', 'cloture'
        ));
    }

    /**
     * GET /api/boutiques/{boutique}/rapports/mensuel?mois=2026-09
     * Rapport mensuel consolidé.
     */
    public function mensuel(Request $request, Boutique $boutique)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        abort_if($boutique->admin_id !== $request->user()->id, 403);

        $carbonMonth = \Illuminate\Support\Carbon::parse($mois . '-01');

        $clotures = $boutique->clotures()
            ->whereYear('date_cloture', $carbonMonth->year)
            ->whereMonth('date_cloture', $carbonMonth->month)
            ->orderBy('date_cloture')
            ->get();

        return response()->json([
            'mois'          => $mois,
            'boutique'      => $boutique->only(['id', 'nom']),
            'total_jours'   => $clotures->count(),
            'total_ventes'  => $clotures->sum('total_ventes'),
            'total_sorties' => $clotures->sum('total_sorties'),
            'solde_net'     => $clotures->sum('total_ventes') - $clotures->sum('total_sorties'),
            'ecarts_signales' => $clotures->where('statut', '!=', 'normal')->count(),
            'clotures'      => $clotures,
        ]);
    }

    /**
     * GET /api/dashboard
     * Vue consolidée admin : toutes ses boutiques.
     */
    public function dashboard(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $dateDebut = $request->query('debut', $request->query('date', now()->toDateString()));
        $dateFin   = $request->query('fin', $dateDebut);

        $boutiques = $request->user()->boutiquesAdmin()
            ->where('actif', true)
            ->with(['parametres'])
            ->get();

        $boutiqueIds = $boutiques->pluck('id');

        $nbVentesTotal = \App\Models\Vente::whereIn('boutique_id', $boutiqueIds)
            ->whereBetween('date_vente', [$dateDebut, $dateFin])
            ->count();

        $topProduits = \App\Models\Vente::whereIn('boutique_id', $boutiqueIds)
            ->whereBetween('date_vente', [$dateDebut, $dateFin])
            ->join('produits', 'produits.id', '=', 'ventes.produit_id')
            ->groupBy('ventes.produit_id', 'produits.nom', 'produits.unite_reference')
            ->selectRaw('produits.nom, produits.unite_reference, CAST(SUM(ventes.quantite) AS DECIMAL(12,2)) as quantite_totale, CAST(SUM(ventes.montant_total) AS DECIMAL(12,2)) as montant_total')
            ->orderByDesc('montant_total')
            ->limit(5)
            ->get();

        $data = $boutiques->map(function ($boutique) use ($dateDebut, $dateFin) {
            $ventesQuery = $boutique->ventes()->whereBetween('date_vente', [$dateDebut, $dateFin]);
            $totalVentes  = $ventesQuery->sum('montant_total');
            $nbVentes     = $ventesQuery->count();
            $totalSorties = $boutique->reapprovisionnements()
                ->whereBetween('date_reappro', [$dateDebut, $dateFin])
                ->sum('montant_depense');
            $stockAlerte  = $boutique->produits()
                ->where('actif', true)
                ->whereNotNull('seuil_alerte_stock')
                ->whereRaw('stock_actuel <= seuil_alerte_stock')
                ->count();

            return [
                'id'            => $boutique->id,
                'nom'           => $boutique->nom,
                'total_ventes'  => (float) $totalVentes,
                'total_sorties' => (float) $totalSorties,
                'solde_jour'    => (float) ($totalVentes - $totalSorties),
                'nb_ventes'     => $nbVentes,
                'produits_en_alerte_stock' => $stockAlerte,
            ];
        });

        $periodeStr = $dateDebut === $dateFin ? $dateDebut : "Du $dateDebut au $dateFin";
        $totalVentesGlobal = (float) $data->sum('total_ventes');
        $panierMoyen = $nbVentesTotal > 0 ? round($totalVentesGlobal / $nbVentesTotal, 0) : 0;

        return response()->json([
            'date'         => $periodeStr,
            'date_debut'   => $dateDebut,
            'date_fin'     => $dateFin,
            'boutiques'    => $data,
            'top_produits' => $topProduits,
            'totaux'       => [
                'ventes'       => $totalVentesGlobal,
                'sorties'      => (float) $data->sum('total_sorties'),
                'solde'        => (float) $data->sum('solde_jour'),
                'nb_ventes'    => $nbVentesTotal,
                'panier_moyen' => $panierMoyen,
            ],
        ]);
    }

    /**
     * GET /api/vendeurs/{vendeur}/ecarts?debut=2026-09-01&fin=2026-09-30
     * Taux d'écart d'une vendeuse sur une période.
     */
    public function ecartVendeur(Request $request, User $vendeur)
    {
        abort_if(!$request->user()->isAdmin(), 403);

        $debut = $request->query('debut', now()->startOfMonth()->toDateString());
        $fin   = $request->query('fin',   now()->endOfMonth()->toDateString());

        $totalVentes  = $vendeur->ventes()->whereBetween('date_vente', [$debut, $fin])->sum('montant_total');
        $totalSorties = \App\Models\Reapprovisionnement::where('vendeur_id', $vendeur->id)
            ->whereBetween('date_reappro', [$debut, $fin])
            ->sum('montant_depense');

        return response()->json([
            'vendeur'        => $vendeur->only(['id', 'nom', 'telephone']),
            'periode'        => compact('debut', 'fin'),
            'total_ventes'   => $totalVentes,
            'total_sorties'  => $totalSorties,
            'ecart_apparent' => $totalVentes - $totalSorties,
        ]);
    }
}
