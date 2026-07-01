<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invitation envoyée par un coach à un client (fiche `customers`) pour qu'il
     * crée son compte et soit directement associé via `customers.account_user_id`.
     */
    public function up(): void
    {
        Schema::create('customer_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('token')->unique();
            $table->foreignId('invited_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['customer_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invitations');
    }
};
