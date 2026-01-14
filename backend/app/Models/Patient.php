<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'date_naissance',
        'telephone',
        'adresse',
        'numero_dossier'
    ];

    public function ordonnances()
    {
        return $this->hasMany(Ordonnance::class);
    }
}
