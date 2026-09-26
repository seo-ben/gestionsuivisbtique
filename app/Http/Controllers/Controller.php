<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Vérifie que l'utilisateur connecté est bien l'admin propriétaire de la boutique.
     */
    protected function checkAdmin(Request $request, Boutique $boutique): void
    {
        $user = $request->user();
        abort_if(!$user || !$user->isAdmin(), 403, 'Action réservée aux administrateurs.');
        abort_if($boutique->admin_id !== $user->id, 403, 'Accès non autorisé à cette boutique.');
    }

    /**
     * Vérifie que l'utilisateur a accès à la boutique (admin propriétaire ou vendeur actif).
     */
    protected function checkAccess(Request $request, Boutique $boutique): void
    {
        $user = $request->user();
        abort_if(!$user, 401);

        if ($user->isAdmin()) {
            abort_if($boutique->admin_id !== $user->id, 403, 'Accès non autorisé à cette boutique.');
        } else {
            $hasAccess = $boutique->vendeurs()
                ->where('users.id', $user->id)
                ->where('boutique_vendeur.actif', true)
                ->exists();
            abort_if(!$hasAccess, 403, 'Accès refusé à cette boutique.');
        }
    }
}
