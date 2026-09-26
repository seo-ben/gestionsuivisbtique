<?php

namespace App\Services;

use App\Models\Boutique;
use App\Models\ClotureCaisse;
use App\Models\ParametresBoutique;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClotureCaisseService
{
    /**
     * Déclenche la clôture d'une boutique pour une date donnée.
     * Si une clôture existe déjà pour ce jour, retourne celle existante.
     */
    public function cloturer(Boutique $boutique, ?Carbon $date = null): ClotureCaisse
    {
        $date ??= Carbon::today();

        // Idempotent : ne crée pas deux clôtures pour le même jour
        $existing = ClotureCaisse::where('boutique_id', $boutique->id)
            ->whereDate('date_cloture', $date)
            ->first();

        if ($existing) {
            return $existing;
        }

        $parametres = $boutique->parametres ?? new ParametresBoutique(['fond_caisse_initial' => 0]);

        // Calcul des totaux du jour
        $totalVentes = $boutique->ventes()
            ->whereDate('date_vente', $date)
            ->sum('montant_total');

        $totalSorties = $boutique->reapprovisionnements()
            ->whereDate('date_reappro', $date)
            ->sum('montant_depense');

        $fond           = (float) $parametres->fond_caisse_initial;
        $soldeTheorique = $fond + $totalVentes - $totalSorties;

        // Déterminer le statut selon les seuils configurés
        $statut = 'normal';
        // On ne peut calculer l'écart que si un solde réel est fourni (contrôle physique)
        // À la clôture automatique, solde_reel reste NULL

        $cloture = ClotureCaisse::create([
            'boutique_id'            => $boutique->id,
            'date_cloture'           => $date,
            'fond_caisse_initial'    => $fond,
            'total_ventes'           => $totalVentes,
            'total_sorties'          => $totalSorties,
            'solde_theorique'        => $soldeTheorique,
            'solde_reel'             => null,
            'ecart'                  => null,
            'statut'                 => $statut,
            'heure_cloture_effective' => Carbon::now(),
        ]);

        return $cloture;
    }

    /**
     * Enregistre un solde réel (contrôle physique) et calcule l'écart.
     */
    public function enregistrerSoldeReel(ClotureCaisse $cloture, float $soldeReel): ClotureCaisse
    {
        $ecart      = $soldeReel - $cloture->solde_theorique;
        $parametres = $cloture->boutique->parametres;

        $statut = 'normal';
        if ($parametres) {
            $ecartAbs = abs($ecart);
            if ($parametres->seuil_alerte_ecart && $ecartAbs > $parametres->seuil_alerte_ecart) {
                $statut = 'ecart_signale';
            }
            if ($parametres->blocage_ecart_actif
                && $parametres->seuil_blocage_ecart
                && $ecartAbs > $parametres->seuil_blocage_ecart) {
                $statut = 'bloquee';
            }
        }

        $cloture->update([
            'solde_reel' => $soldeReel,
            'ecart'      => $ecart,
            'statut'     => $statut,
        ]);

        return $cloture->fresh();
    }

    /**
     * Lance la clôture automatique de toutes les boutiques actives.
     * Appelé par le scheduler Laravel (23h59 chaque jour).
     */
    public function cloturerToutesBoutiques(): array
    {
        $boutiques = Boutique::where('actif', true)->get();
        $resultats = [];

        foreach ($boutiques as $boutique) {
            try {
                $cloture     = $this->cloturer($boutique);
                $resultats[] = ['boutique_id' => $boutique->id, 'statut' => 'ok', 'cloture_id' => $cloture->id];
            } catch (\Throwable $e) {
                $resultats[] = ['boutique_id' => $boutique->id, 'statut' => 'erreur', 'message' => $e->getMessage()];
            }
        }

        return $resultats;
    }
}
