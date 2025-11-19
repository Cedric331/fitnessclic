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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator (admin or client)
            $table->string('title'); // Title
            $table->text('description')->nullable(); // Description
            $table->string('suggested_duration')->nullable(); // Suggested duration (text field)
            $table->boolean('is_shared')->default(false); // Share with other members (default false)
            $table->timestamps();

            $table->index(['user_id', 'is_shared']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
