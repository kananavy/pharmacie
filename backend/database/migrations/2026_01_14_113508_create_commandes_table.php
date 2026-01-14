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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket')->unique();
            $table->foreignId('vendeur_id')->constrained('users');
            $table->decimal('total', 10, 2);
            $table->enum('statut', ['en_attente', 'payee', 'annulee'])->default('en_attente');
            $table->foreignId('ordonnance_id')->nullable()->constrained();
            $table->foreignId('patient_id')->nullable()->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
