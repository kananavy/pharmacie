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
        Schema::table('medicaments', function (Blueprint $table) {
            if (Schema::hasColumn('medicaments', 'quantite')) {
                $table->dropColumn('quantite');
            }
            if (Schema::hasColumn('medicaments', 'date_peremption')) {
                $table->dropColumn('date_peremption');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            // Re-add columns for rollback, assuming their original types
            if (!Schema::hasColumn('medicaments', 'quantite')) {
                $table->integer('quantite')->default(0); // Assuming it was an integer with a default
            }
            if (!Schema::hasColumn('medicaments', 'date_peremption')) {
                $table->date('date_peremption')->nullable(); // Assuming it was a date and nullable
            }
        });
    }
};
