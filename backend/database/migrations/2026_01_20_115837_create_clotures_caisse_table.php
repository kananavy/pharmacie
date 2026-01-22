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
        Schema::create('clotures_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('pharmacie_id')->nullable(); // For future multi-sites
            $table->timestamp('date_ouverture')->useCurrent();
            $table->timestamp('date_cloture')->nullable(); // Will be filled upon closing
            $table->decimal('total_theorique', 10, 2)->default(0);
            $table->decimal('total_reel', 10, 2)->default(0);
            $table->decimal('ecart', 10, 2)->default(0);
            $table->text('commentaires')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clotures_caisse');
    }
};
