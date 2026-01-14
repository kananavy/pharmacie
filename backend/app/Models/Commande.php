<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_ticket',
        'vendeur_id',
        'total',
        'statut',
        'ordonnance_id',
        'patient_id',
        'notes'
    ];

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function ordonnance()
    {
        return $this->belongsTo(Ordonnance::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function details()
    {
        return $this->hasMany(DetailCommande::class);
    }

    public function vente()
    {
        return $this->hasOne(Vente::class);
    }

    // Generate unique ticket number
    public static function generateTicketNumber()
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'CMD-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
