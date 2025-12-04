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
            // Ajouter le champ sets_count (nombre de séries)
            $table->integer('sets_count')->nullable()->after('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercise', function (Blueprint $table) {
            $table->dropColumn('sets_count');
        });
    }
};

