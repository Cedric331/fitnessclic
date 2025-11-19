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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User who creates the session
            $table->foreignId('client_id')->constrained('customers')->onDelete('cascade'); // Customer for whom the session is created
            $table->string('name')->nullable(); // Session name/title (optional)
            $table->text('notes')->nullable(); // General notes about the session
            $table->date('session_date')->nullable(); // Session date
            $table->timestamps();

            $table->index(['user_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
