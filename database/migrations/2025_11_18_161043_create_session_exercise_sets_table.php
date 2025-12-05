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
            $table->integer('set_number')->default(1);
            $table->integer('repetitions')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('rest_time')->nullable();
            $table->string('duration')->nullable();
            $table->integer('order')->default(0);
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

