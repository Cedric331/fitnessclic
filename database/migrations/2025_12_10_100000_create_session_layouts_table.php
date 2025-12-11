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
        Schema::create('session_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->json('layout_data'); // Stocke tous les éléments du canvas (images, textes, positions, tailles, etc.)
            $table->integer('canvas_width')->default(800); // Largeur du canvas en pixels
            $table->integer('canvas_height')->default(1000); // Hauteur du canvas en pixels (format A4 portrait)
            $table->timestamps();

            $table->index('session_id');
            $table->unique('session_id'); // Une seule mise en page par séance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_layouts');
    }
};

