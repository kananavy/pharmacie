<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicament_id')->constrained('medicaments')->onDelete('cascade');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('set null');
            $table->string('numero_lot');
            $table->integer('quantite_initiale');
            $table->integer('quantite_actuelle');
            $table->decimal('prix_achat', 10, 2);
            $table->date('date_fabrication')->nullable();
            $table->date('date_expiration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
