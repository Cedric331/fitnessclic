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
            $table->string('custom_exercise_name')->nullable()->after('exercise_id');
            $table->boolean('use_duration')->default(false)->after('repetitions');
            $table->boolean('use_bodyweight')->default(false)->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercise', function (Blueprint $table) {
            $table->dropColumn(['custom_exercise_name', 'use_duration', 'use_bodyweight']);
        });
    }
};
