<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->foreignId('commande_id')->nullable()->constrained();
            $table->enum('mode_paiement', ['especes', 'carte', 'mobile_money'])->default('especes');
            $table->decimal('montant_recu', 10, 2)->nullable();
            $table->decimal('montant_rendu', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropForeign(['commande_id']);
            $table->dropColumn(['commande_id', 'mode_paiement', 'montant_recu', 'montant_rendu']);
        });
    }
};
