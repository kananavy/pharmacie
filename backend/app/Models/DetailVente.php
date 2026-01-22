<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailVente extends Model
{
    use HasFactory;

    protected $table = 'details_vente';

    protected $fillable = [
        'vente_id',
        'medicament_id',
        'lot_id',
        'quantite',
        'prix_unitaire',
        'type_vente',
        'assurance_taux_applique',
        'part_client_item',
        'part_assurance_item'
    ];

    public function vente()
    {
        return $this->belongsTo(Vente::class);
    }

    public function medicament()
    {
        return $this->belongsTo(Medicament::class);
    }

    /**
     * Get the lot that was sold.
     */
    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}
