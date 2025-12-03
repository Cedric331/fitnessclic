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
        // Vérifier si la colonne existe avant de la supprimer
        if (Schema::hasColumn('training_sessions', 'customer_id')) {
            Schema::table('training_sessions', function (Blueprint $table) {
                // Supprimer la contrainte de clé étrangère si elle existe
                // Laravel génère automatiquement le nom de la contrainte basé sur la table et la colonne
                try {
                    $table->dropForeign(['customer_id']);
                } catch (\Exception $e) {
                    // La contrainte n'existe pas ou une erreur s'est produite, continuer
                    // Cela peut arriver si la contrainte a déjà été supprimée ou n'existe pas
                }
                
                // Supprimer la colonne customer_id
                $table->dropColumn('customer_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            // Recréer la colonne customer_id (nullable pour compatibilité)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
        });
    }
};

