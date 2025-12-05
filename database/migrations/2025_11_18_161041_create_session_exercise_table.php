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
            $table->integer('repetitions')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('rest_time')->nullable();
            $table->string('duration')->nullable();
            $table->text('additional_description')->nullable();
            $table->integer('order')->default(0);
            $table->integer('sets_count')->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
            $table->enum('block_type', ['standard', 'set'])->nullable();
            $table->tinyInteger('position_in_block')->nullable()->default(0);
            $table->timestamps();

            $table->index(['session_id', 'order']);
            $table->index(['block_id', 'position_in_block']);
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
