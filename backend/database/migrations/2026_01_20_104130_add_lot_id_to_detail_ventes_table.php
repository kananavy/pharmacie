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
        Schema::table('details_vente', function (Blueprint $table) {
            $table->foreignId('lot_id')->nullable()->constrained('lots')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('details_vente', function (Blueprint $table) {
            $table->dropForeign(['lot_id']);
            $table->dropColumn('lot_id');
        });
    }
};
