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
        Schema::create('session_exercise_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_exercise_id')->constrained('session_exercise')->onDelete('cascade');
            $table->integer('set_number')->default(1); // Numéro de la série (1, 2, 3, etc.)
            $table->integer('repetitions')->nullable(); // Nombre de répétitions pour cette série
            $table->decimal('weight', 8, 2)->nullable(); // Charge/poids pour cette série
            $table->string('rest_time')->nullable(); // Temps de repos après cette série
            $table->string('duration')->nullable(); // Durée (si c'est un exercice basé sur le temps)
            $table->integer('order')->default(0); // Ordre de la série (pour réordonner si besoin)
            $table->timestamps();

            $table->index(['session_exercise_id', 'order']);
            $table->index(['session_exercise_id', 'set_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_exercise_sets');
    }
};

