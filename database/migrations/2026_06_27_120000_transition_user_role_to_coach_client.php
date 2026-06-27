<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Transition the `users.role` enum from (admin, customer) to
     * (admin, coach, client). Existing non-admin users were "customers"
     * in the schema but are in fact coaches, so they are backfilled to `coach`.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Widen the enum to hold both old and new values during backfill.
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','customer','coach','client') NOT NULL DEFAULT 'coach'");
            DB::table('users')->where('role', 'customer')->update(['role' => 'coach']);
            // Narrow the enum to its final set.
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','coach','client') NOT NULL DEFAULT 'coach'");

            return;
        }

        // Other drivers (e.g. SQLite for tests): the enum is a CHECK-constrained
        // varchar. Relax it to a plain string so coach/client become valid, then backfill.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('coach')->change();
        });
        DB::table('users')->where('role', 'customer')->update(['role' => 'coach']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','customer','coach','client') NOT NULL DEFAULT 'customer'");
            DB::table('users')->whereIn('role', ['coach', 'client'])->update(['role' => 'customer']);
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','customer') NOT NULL DEFAULT 'customer'");

            return;
        }

        DB::table('users')->whereIn('role', ['coach', 'client'])->update(['role' => 'customer']);
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->change();
        });
    }
};
