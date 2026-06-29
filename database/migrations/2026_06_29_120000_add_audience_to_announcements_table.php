<?php

use App\Enums\AnnouncementAudience;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cible d'affichage d'une annonce : par défaut les coachs uniquement,
     * éventuellement étendue aux clients.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('audience')
                ->default(AnnouncementAudience::COACHES->value)
                ->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
