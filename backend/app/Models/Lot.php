<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicament_id',
        'fournisseur_id',
        'numero_lot',
        'quantite_initiale',
        'quantite_actuelle',
        'prix_achat',
        'date_fabrication',
        'date_expiration',
    ];

    /**
     * Get the medicament that owns the lot.
     */
    public function medicament(): BelongsTo
    {
        return $this->belongsTo(Medicament::class);
    }

    /**
     * Get the fournisseur that provided the lot.
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }
}
