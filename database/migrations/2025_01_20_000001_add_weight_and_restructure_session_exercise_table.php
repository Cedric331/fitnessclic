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
        Schema::table('session_exercise', function (Blueprint $table) {
            // Ajouter le champ weight (charge)
            $table->decimal('weight', 8, 2)->nullable()->after('repetitions');
            
            // Renommer additional_description en notes pour plus de clarté
            // On garde les deux colonnes temporairement pour la migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercise', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};

