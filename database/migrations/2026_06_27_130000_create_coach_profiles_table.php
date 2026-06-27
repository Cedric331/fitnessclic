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
        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('hourly_rate')->nullable(); // en centimes d'euro
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->json('specialties')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coach_profiles');
    }
};
