<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Boutique extends Model
{
    protected $fillable = ['uuid', 'admin_id', 'nom', 'adresse', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn ($b) => $b->uuid ??= (string) Str::uuid());
        // Créer les paramètres par défaut à la création
        static::created(fn ($b) => $b->parametres()->create([]));
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function vendeurs()
    {
        return $this->belongsToMany(User::class, 'boutique_vendeur', 'boutique_id', 'vendeur_id')
                    ->withPivot(['date_rattachement', 'actif'])
                    ->wherePivot('actif', true);
    }

    public function activites()
    {
        return $this->belongsToMany(Activite::class, 'boutique_activite');
    }

    public function parametres()
    {
        return $this->hasOne(ParametresBoutique::class, 'boutique_id');
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }

    public function reapprovisionnements()
    {
        return $this->hasMany(Reapprovisionnement::class);
    }

    public function clotures()
    {
        return $this->hasMany(ClotureCaisse::class);
    }

    public function autorisations()
    {
        return $this->hasMany(AutorisationReapprovisionnement::class);
    }
}
