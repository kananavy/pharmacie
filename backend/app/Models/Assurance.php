<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assurance extends Model
{
    use HasFactory;

    protected $table = 'assurances';

    protected $fillable = [
        'nom',
        'taux_prise_en_charge_default',
        'contact_person',
        'contact_email',
        'contact_phone',
        'adresse',
        'code',
    ];

    protected $casts = [
        'taux_prise_en_charge_default' => 'float',
    ];
}
