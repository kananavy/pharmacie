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
            $table->decimal('assurance_taux_applique', 5, 2)->nullable()->after('type_vente');
            $table->decimal('part_client_item', 10, 2)->nullable()->after('assurance_taux_applique');
            $table->decimal('part_assurance_item', 10, 2)->nullable()->after('part_client_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('details_vente', function (Blueprint $table) {
            $table->dropColumn(['assurance_taux_applique', 'part_client_item', 'part_assurance_item']);
        });
    }
};
