<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modalité des séances proposées par le coach : présentiel, visio ou les deux.
     */
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->string('coaching_mode', 20)->default('in_person')->after('specialties');
        });
    }

    public function down(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->dropColumn('coaching_mode');
        });
    }
};
