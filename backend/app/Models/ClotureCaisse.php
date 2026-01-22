<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClotureCaisse extends Model
{
    use HasFactory;

    protected $table = 'clotures_caisse';

    protected $fillable = [
        'user_id',
        'pharmacie_id',
        'date_ouverture',
        'date_cloture',
        'total_theorique',
        'total_reel',
        'ecart',
        'commentaires',
    ];

    protected $casts = [
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
    ];

    /**
     * Get the user (cashier) who performed the closing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
