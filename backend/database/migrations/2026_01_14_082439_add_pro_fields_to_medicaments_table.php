<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->integer('max_stock')->default(100)->after('seuil_alerte');
            $table->decimal('prix_achat', 10, 2)->default(0)->after('prix');
            $table->string('unite_emballage')->default('Boîte')->after('categorie');
            $table->integer('quantite_par_emballage')->default(1)->after('unite_emballage');
            $table->string('emplacement')->nullable()->after('seuil_alerte');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->dropForeign(['fournisseur_id']);
            $table->dropColumn(['max_stock', 'prix_achat', 'unite_emballage', 'quantite_par_emballage', 'emplacement', 'fournisseur_id']);
        });
    }
};
