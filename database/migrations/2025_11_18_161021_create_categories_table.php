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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Category name
            $table->enum('type', ['public', 'private'])->default('private'); // Type: public or private
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // null for public categories (admin), user_id for private ones
            // Note: For public categories created by admin, user_id can be null or the admin's ID
            $table->timestamps();

            $table->index(['type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
