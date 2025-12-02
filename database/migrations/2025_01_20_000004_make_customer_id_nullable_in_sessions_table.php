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
        Schema::table('training_sessions', function (Blueprint $table) {
            // Rendre customer_id nullable pour permettre les séances sans client unique
            // (les clients seront maintenant gérés via la table pivot session_customer)
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            // Remettre customer_id comme non-nullable si nécessaire
            // Note: Cela peut échouer si des séances existent sans customer_id
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};

