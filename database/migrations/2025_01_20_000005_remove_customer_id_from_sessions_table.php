<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                try {
                    // Récupérer le nom réel de la contrainte depuis la base de données
                    $constraint = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'training_sessions' 
                        AND COLUMN_NAME = 'customer_id' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                        LIMIT 1
                    ");
                    
                    if (!empty($constraint)) {
                        $constraintName = $constraint[0]->CONSTRAINT_NAME;
                        $table->dropForeign([$constraintName]);
                    }
                } catch (\Exception $e) {
                    // La contrainte n'existe pas ou une erreur s'est produite, continuer
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

