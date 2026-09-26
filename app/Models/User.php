<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'uuid', 'nom', 'telephone', 'email', 'password', 'role', 'actif',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($user) => $user->uuid ??= (string) Str::uuid());
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVendeur(): bool
    {
        return $this->role === 'vendeur';
    }

    // Relations
    public function boutiquesAdmin()
    {
        return $this->hasMany(Boutique::class, 'admin_id');
    }

    public function boutiques()
    {
        return $this->belongsToMany(Boutique::class, 'boutique_vendeur', 'vendeur_id', 'boutique_id')
                    ->withPivot(['date_rattachement', 'actif'])
                    ->wherePivot('actif', true);
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class, 'vendeur_id');
    }

    public function autorisations()
    {
        return $this->hasMany(AutorisationReapprovisionnement::class, 'vendeur_id');
    }

    public function paiements()
    {
        return $this->hasMany(PaiementBoutiquier::class, 'vendeur_id');
    }
}
