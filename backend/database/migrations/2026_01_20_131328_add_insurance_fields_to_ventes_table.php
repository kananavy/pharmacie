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
        Schema::table('ventes', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null')->after('user_id');
            $table->decimal('montant_paye_client', 10, 2)->nullable()->after('montant_rendu');
            $table->decimal('montant_du_par_assurance', 10, 2)->nullable()->after('montant_paye_client');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn(['patient_id', 'montant_paye_client', 'montant_du_par_assurance']);
        });
    }
};
