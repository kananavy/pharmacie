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
        Schema::table('medicaments', function (Blueprint $table) {
            $table->string('unite_stock')->default('boite'); // e.g. "boite", "flacon"
            $table->integer('unites_par_boite')->default(1); // e.g. 20 pills per box
            $table->integer('stock_vrac')->default(0); // loose units available (opened box)
            $table->decimal('prix_unitaire', 10, 2)->nullable(); // price per unit (pill)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->dropColumn(['unite_stock', 'unites_par_boite', 'stock_vrac', 'prix_unitaire']);
        });
    }
};
