<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activite extends Model
{
    public $timestamps = false;
    protected $fillable = ['nom'];

    public function boutiques()
    {
        return $this->belongsToMany(Boutique::class, 'boutique_activite');
    }
}
