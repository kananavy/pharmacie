<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vente extends Model
{
    use HasFactory;

    protected $fillable = [
        'total',
        'user_id',
        'ordonnance_id',
        'commande_id',
        'mode_paiement',
        'montant_recu',
        'montant_rendu'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ordonnance()
    {
        return $this->belongsTo(Ordonnance::class);
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function details()
    {
        return $this->hasMany(DetailVente::class);
    }
}
