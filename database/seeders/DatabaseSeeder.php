<?php

namespace Database\Seeders;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer le compte admin
        $admin = User::firstOrCreate(
            ['telephone' => '0600000000'],
            [
                'uuid'      => (string) Str::uuid(),
                'nom'       => 'Administrateur',
                'email'     => 'admin@gesp.local',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'actif'     => true,
            ]
        );

        // Créer un vendeur exemple
        $vendeur = User::firstOrCreate(
            ['telephone' => '0611111111'],
            [
                'uuid'      => (string) Str::uuid(),
                'nom'       => 'Aminata Diallo',
                'email'     => null,
                'password'  => Hash::make('password'),
                'role'      => 'vendeur',
                'actif'     => true,
            ]
        );

        // Types d'activités
        $poissonnerie = Activite::firstOrCreate(['nom' => 'Poissonnerie']);
        $boucherie    = Activite::firstOrCreate(['nom' => 'Boucherie']);
        $epicerie     = Activite::firstOrCreate(['nom' => 'Épicerie']);
        $autre        = Activite::firstOrCreate(['nom' => 'Autre']);

        // Créer une boutique exemple
        $boutique = \App\Models\Boutique::firstOrCreate(
            ['nom' => 'Boutique Marché Central'],
            [
                'admin_id' => $admin->id,
                'adresse'  => 'Marché Central, Stand 12',
            ]
        );

        $boutique->activites()->syncWithoutDetaching([$poissonnerie->id, $boucherie->id]);

        // Rattacher le vendeur
        $boutique->vendeurs()->syncWithoutDetaching([
            $vendeur->id => [
                'date_rattachement' => now()->toDateString(),
                'actif'             => true,
            ]
        ]);

        // Créer quelques produits si aucun
        if ($boutique->produits()->count() === 0) {
            $boutique->produits()->createMany([
                ['nom' => 'Thiof (kg)',        'prix_actuel' => 4500,  'unite_reference' => 'kg',     'stock_actuel' => 20],
                ['nom' => 'Capitaine (kg)',    'prix_actuel' => 3200,  'unite_reference' => 'kg',     'stock_actuel' => 15, 'seuil_alerte_stock' => 5],
                ['nom' => 'Viande bœuf (kg)', 'prix_actuel' => 5000,  'unite_reference' => 'kg',     'stock_actuel' => 10],
                ['nom' => 'Mouton (kg)',       'prix_actuel' => 6000,  'unite_reference' => 'kg',     'stock_actuel' => 8],
                ['nom' => 'Glace (sac)',       'prix_actuel' => 500,   'unite_reference' => 'sac',    'stock_actuel' => 30],
            ]);
        }

        $this->command->info('✅ Seeder terminé.');
        $this->command->info('Admin    → téléphone: 0600000000 | password: password');
        $this->command->info('Vendeur  → téléphone: 0611111111 | password: password');
    }
}
