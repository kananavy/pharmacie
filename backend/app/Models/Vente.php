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
        'patient_id',
        'ordonnance_id',
        'commande_id',
        'mode_paiement',
        'montant_recu',
        'montant_rendu',
        'montant_paye_client',
        'montant_du_par_assurance',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
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
