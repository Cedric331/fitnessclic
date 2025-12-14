<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->uuid('share_token')->nullable()->unique()->after('session_date');
        });

        // Générer des tokens pour les sessions existantes qui n'en ont pas
        $sessions = DB::table('training_sessions')->whereNull('share_token')->get();
        foreach ($sessions as $session) {
            DB::table('training_sessions')
                ->where('id', $session->id)
                ->update(['share_token' => (string) Str::uuid()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
