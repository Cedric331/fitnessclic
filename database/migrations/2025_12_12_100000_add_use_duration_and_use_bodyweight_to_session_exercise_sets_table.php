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
        Schema::table('session_exercise_sets', function (Blueprint $table) {
            $table->boolean('use_duration')->default(false)->after('duration');
            $table->boolean('use_bodyweight')->default(false)->after('use_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercise_sets', function (Blueprint $table) {
            $table->dropColumn(['use_duration', 'use_bodyweight']);
        });
    }
};
