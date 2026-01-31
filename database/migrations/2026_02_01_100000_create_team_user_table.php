<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // Backfill existing memberships from users.team_id
        if (Schema::hasColumn('users', 'team_id')) {
            $rows = DB::table('users')
                ->whereNotNull('team_id')
                ->select('team_id', 'id')
                ->get();

            foreach ($rows as $row) {
                DB::table('team_user')->updateOrInsert(
                    ['team_id' => $row->team_id, 'user_id' => $row->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};

