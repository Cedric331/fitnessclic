<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Garde-fou : un coach ne peut pas avoir deux fiches client avec le même
     * email (clé de rapprochement de l'auto-liaison vers un compte client).
     * Les emails NULL restent autorisés en plusieurs exemplaires.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['user_id', 'email'], 'customers_user_id_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_user_id_email_unique');
        });
    }
};
