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
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->integer('amount');
            $table->string('reason'); // subscription_initial, subscription_renewal, image_generation
            $table->enum('status', ['success', 'error', 'pending'])->default('success');
            $table->text('error_log')->nullable();
            $table->json('metadata')->nullable(); // For storing additional info like exercise name, gender, etc.
            $table->integer('balance_after')->nullable(); // Balance after this transaction
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_credits');
    }
};

