<?php

namespace App\Services;

use App\Models\Reapprovisionnement;
use App\Models\SyncLog;
use App\Models\Vente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncService
{
    /**
     * Synchronise un lot de ventes envoyées par l'app mobile.
     * Utilise UPSERT sur uuid pour garantir l'idempotence.
     *
     * @param  array  $items  Tableau de ventes (format validé)
     * @param  int    $vendeurId
     * @return array  ['synced' => int, 'skipped' => int]
     */
    public function syncVentes(array $items, int $vendeurId): array
    {
        $synced  = 0;
        $skipped = 0;
        $now     = Carbon::now();

        DB::transaction(function () use ($items, $vendeurId, $now, &$synced, &$skipped) {
            foreach ($items as $item) {
                $existing = Vente::where('uuid', $item['uuid'])->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                $vente = Vente::create([
                    ...$item,
                    'vendeur_id' => $vendeurId,
                    'synced_at'  => $now,
                ]);

                // Réconcilier le stock du produit après chaque vente synchronisée
                $vente->produit->reconcilierStock();

                SyncLog::create([
                    'table_name'  => 'ventes',
                    'record_uuid' => $vente->uuid,
                    'vendeur_id'  => $vendeurId,
                    'device_id'   => $item['device_id'] ?? null,
                    'action'      => 'create',
                    'synced_at'   => $now,
                ]);

                $synced++;
            }
        });

        return compact('synced', 'skipped');
    }

    /**
     * Synchronise un lot de réapprovisionnements.
     * Vérifie que l'autorisation est encore active côté serveur (sécurité).
     *
     * @param  array  $items
     * @param  int    $vendeurId
     * @return array  ['synced' => int, 'skipped' => int, 'rejected' => int]
     */
    public function syncReapprovisionnements(array $items, int $vendeurId): array
    {
        $synced   = 0;
        $skipped  = 0;
        $rejected = 0;
        $now      = Carbon::now();

        DB::transaction(function () use ($items, $vendeurId, $now, &$synced, &$skipped, &$rejected) {
            foreach ($items as $item) {
                // Dédoublonnage
                if (Reapprovisionnement::where('uuid', $item['uuid'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Vérification de l'autorisation côté serveur
                $autorisation = \App\Models\AutorisationReapprovisionnement::find($item['autorisation_id']);
                if (!$autorisation || !$autorisation->estActive()) {
                    $rejected++;
                    continue;
                }

                $reappro = Reapprovisionnement::create([
                    ...$item,
                    'vendeur_id' => $vendeurId,
                    'synced_at'  => $now,
                ]);

                // Réconcilier le stock
                $reappro->produit->reconcilierStock();

                // Passer l'autorisation ponctuelle à 'utilisee'
                if ($autorisation->type === 'ponctuelle') {
                    $autorisation->update(['statut' => 'utilisee']);
                }

                SyncLog::create([
                    'table_name'  => 'reapprovisionnements',
                    'record_uuid' => $reappro->uuid,
                    'vendeur_id'  => $vendeurId,
                    'device_id'   => $item['device_id'] ?? null,
                    'action'      => 'create',
                    'synced_at'   => $now,
                ]);

                $synced++;
            }
        });

        return compact('synced', 'skipped', 'rejected');
    }
}
