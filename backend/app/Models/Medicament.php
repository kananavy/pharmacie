<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\DetailVente;

class Medicament extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'categorie',
        'unite_emballage',
        'quantite_par_emballage',
        'prix',
        'prix_achat',
        'stock',
        'date_expiration',
        'ordonnance_requise',
        'seuil_alerte',
        'emplacement',
        'max_stock',
        'fournisseur_id'
    ];

    public function details()
    {
        return $this->hasMany(DetailVente::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class);
    }
}
