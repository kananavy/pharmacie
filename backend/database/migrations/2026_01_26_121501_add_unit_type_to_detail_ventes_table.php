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
        Schema::table('details_vente', function (Blueprint $table) {
            $table->string('type_vente')->default('boite'); // 'boite' or 'unite'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('details_vente', function (Blueprint $table) {
            $table->dropColumn('type_vente');
        });
    }
};
