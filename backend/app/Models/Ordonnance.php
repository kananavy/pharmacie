<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ordonnance extends Model
{
    use HasFactory;

    protected $fillable = ['numero', 'medecin', 'date_ordonnance', 'patient_nom'];

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }
}
