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
        Schema::create('session_exercise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->integer('repetitions')->nullable(); // Number of repetitions
            $table->string('rest_time')->nullable(); // Rest time (text field)
            $table->string('duration')->nullable(); // Duration (text field)
            $table->text('additional_description')->nullable(); // Additional description
            $table->integer('order')->default(0); // Order in the session
            $table->timestamps();

            $table->index(['session_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_exercise');
    }
};
