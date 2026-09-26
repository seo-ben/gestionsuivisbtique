<?php

use App\Services\ClotureCaisseService;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes (Scheduler) — GESP
|--------------------------------------------------------------------------
| Le scheduler Laravel est lancé par cron :
| * * * * * cd /chemin/vers/projet && php artisan schedule:run >> /dev/null 2>&1
*/

// Clôture automatique de toutes les boutiques actives à 23h59 chaque jour
Schedule::call(function () {
    $service = app(ClotureCaisseService::class);
    $result  = $service->cloturerToutesBoutiques();
    \Illuminate\Support\Facades\Log::info('Clôtures automatiques', $result);
})->dailyAt('23:59')->name('cloture-automatique')->withoutOverlapping();

// Mettre à jour le statut des échéances en retard chaque matin à 06h00
Schedule::call(function () {
    $updated = \App\Models\EcheanceEmprunt::where('statut', 'a_venir')
        ->where('date_echeance', '<', now()->toDateString())
        ->update(['statut' => 'en_retard']);

    \Illuminate\Support\Facades\Log::info("Échéances en retard mise à jour : {$updated}");
})->dailyAt('06:00')->name('update-echeances-retard');
