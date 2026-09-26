<?php

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\AutorisationReapprovisionnement;
use App\Models\Boutique;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GespApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendeur;
    private Boutique $boutique;
    private Produit $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'nom'       => 'Admin Test',
            'telephone' => '0600000000',
            'email'     => 'admin@test.local',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'actif'     => true,
        ]);

        $this->vendeur = User::create([
            'nom'       => 'Vendeuse Test',
            'telephone' => '0611111111',
            'email'     => null,
            'password'  => bcrypt('password'),
            'role'      => 'vendeur',
            'actif'     => true,
        ]);

        $this->boutique = Boutique::create([
            'admin_id' => $this->admin->id,
            'nom'      => 'Boutique Alpha',
            'adresse'  => 'Marché Central',
            'actif'    => true,
        ]);

        $this->boutique->vendeurs()->attach($this->vendeur->id, [
            'date_rattachement' => now()->toDateString(),
            'actif'             => true,
        ]);

        $this->produit = $this->boutique->produits()->create([
            'nom'             => 'Poisson Frais',
            'prix_actuel'     => 3000,
            'unite_reference' => 'kg',
            'stock_actuel'    => 50,
        ]);
    }

    public function test_login_returns_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'telephone' => '0600000000',
            'password'  => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'uuid', 'nom', 'telephone', 'role']]);

        $token = $response->json('token');

        $meResponse = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('nom', 'Admin Test');
    }

    public function test_admin_can_create_boutique_and_update_parameters(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/boutiques', [
            'nom'     => 'Boutique Beta',
            'adresse' => 'Quartier Sud',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('nom', 'Boutique Beta');

        $boutiqueId = $response->json('id');

        $paramResponse = $this->actingAs($this->admin)->putJson("/api/boutiques/{$boutiqueId}/parametres", [
            'fond_caisse_initial'  => 25000,
            'seuil_alerte_ecart'   => 5000,
            'blocage_ecart_actif'  => true,
            'seuil_blocage_ecart'  => 10000,
        ]);

        $paramResponse->assertStatus(200);
        $this->assertEquals(25000, (float) $paramResponse->json('parametres.fond_caisse_initial'));
    }

    public function test_vendeur_can_access_their_boutique_products(): void
    {
        $response = $this->actingAs($this->vendeur)->getJson("/api/boutiques/{$this->boutique->id}/produits");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['nom' => 'Poisson Frais']);
    }

    public function test_offline_sales_sync_idempotency_and_stock_reduction(): void
    {
        $saleUuid = (string) Str::uuid();

        $payload = [
            'ventes' => [
                [
                    'uuid'              => $saleUuid,
                    'boutique_id'       => $this->boutique->id,
                    'produit_id'        => $this->produit->id,
                    'quantite'          => 5,
                    'unite'             => 'kg',
                    'prix_unitaire'     => 3000,
                    'montant_total'     => 15000,
                    'date_vente'        => now()->toDateString(),
                    'heure_vente'       => '14:30:00',
                    'created_at_local'  => now()->toDateTimeString(),
                    'device_id'         => 'phone-aminata-1',
                ],
            ],
        ];

        // Première sync
        $response1 = $this->actingAs($this->vendeur)->postJson('/api/ventes/sync', $payload);
        $response1->assertStatus(200)->assertJsonPath('synced', 1)->assertJsonPath('skipped', 0);

        // Vérification déduction de stock
        $this->assertEquals(45, (float) $this->produit->fresh()->stock_actuel);

        // Deuxième sync (idempotence - doublon évité)
        $response2 = $this->actingAs($this->vendeur)->postJson('/api/ventes/sync', $payload);
        $response2->assertStatus(200)->assertJsonPath('synced', 0)->assertJsonPath('skipped', 1);

        // Le stock ne doit pas avoir diminué une deuxième fois
        $this->assertEquals(45, (float) $this->produit->fresh()->stock_actuel);
    }

    public function test_reapprovisionnement_requires_active_authorization(): void
    {
        // Créer une autorisation ponctuelle
        $autorisation = AutorisationReapprovisionnement::create([
            'boutique_id'   => $this->boutique->id,
            'vendeur_id'    => $this->vendeur->id,
            'accordee_par'  => $this->admin->id,
            'type'          => 'ponctuelle',
            'date_debut'    => now()->toDateString(),
            'statut'        => 'active',
        ]);

        $reapproUuid = (string) Str::uuid();

        $payload = [
            'reapprovisionnements' => [
                [
                    'uuid'              => $reapproUuid,
                    'autorisation_id'   => $autorisation->id,
                    'boutique_id'       => $this->boutique->id,
                    'produit_id'        => $this->produit->id,
                    'quantite'          => 20,
                    'unite'             => 'kg',
                    'montant_depense'   => 40000,
                    'fournisseur'       => 'Pêcheur Port',
                    'date_reappro'      => now()->toDateString(),
                    'heure_reappro'     => '10:00:00',
                    'created_at_local'  => now()->toDateTimeString(),
                    'device_id'         => 'phone-aminata-1',
                ],
            ],
        ];

        $response = $this->actingAs($this->vendeur)->postJson('/api/reapprovisionnements/sync', $payload);
        $response->assertStatus(200)->assertJsonPath('synced', 1);

        // L'autorisation ponctuelle doit être marquée 'utilisee'
        $this->assertEquals('utilisee', $autorisation->fresh()->statut);

        // Le stock doit être augmenté de 20 (50 initial + 20 = 70)
        $this->assertEquals(70, (float) $this->produit->fresh()->stock_actuel);
    }

    public function test_cloture_caisse_and_solde_reel(): void
    {
        $this->boutique->parametres()->update([
            'fond_caisse_initial' => 20000,
            'seuil_alerte_ecart'  => 2000,
        ]);

        // Déclencher clôture
        $response = $this->actingAs($this->admin)->postJson("/api/boutiques/{$this->boutique->id}/clotures");
        $response->assertStatus(201);

        $clotureId = $response->json('id');
        $this->assertEquals(20000, (float) $response->json('solde_theorique'));

        // Enregistrer contrôle physique (solde réel avec écart de 500)
        $soldeReelResponse = $this->actingAs($this->admin)->putJson("/api/clotures/{$clotureId}/solde-reel", [
            'solde_reel' => 19500,
        ]);

        $soldeReelResponse->assertStatus(200);
        $this->assertEquals(-500, (float) $soldeReelResponse->json('ecart'));
        $this->assertEquals('normal', $soldeReelResponse->json('statut'));
    }

    public function test_emprunt_creation_and_repayment(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/emprunts', [
            'organisme_preteur' => 'Banque Agricole',
            'capital_emprunte'  => 600000,
            'taux_interet'      => 5,
            'date_octroi'       => now()->toDateString(),
            'duree_mois'        => 6,
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(6, 'echeances');
        $this->assertEquals(600000, (float) $response->json('capital_restant_du'));

        $echeanceId = $response->json('echeances.0.id');

        $payerResponse = $this->actingAs($this->admin)->putJson("/api/echeances/{$echeanceId}/payer", [
            'montant_paye'  => 100000,
            'date_paiement' => now()->toDateString(),
        ]);

        $payerResponse->assertStatus(200)
            ->assertJsonPath('echeance.statut', 'payee');
        $this->assertEquals(500000, (float) $payerResponse->json('emprunt.capital_restant_du'));
    }

    public function test_admin_dashboard_summary(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure(['date', 'boutiques', 'totaux' => ['ventes', 'sorties', 'solde']]);
    }
}
