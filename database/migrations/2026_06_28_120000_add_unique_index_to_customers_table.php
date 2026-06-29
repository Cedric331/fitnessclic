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
     * Garde-fou : un coach ne peut pas avoir deux fiches client avec le même
     * email (clé de rapprochement de l'auto-liaison vers un compte client).
     * Les emails NULL restent autorisés en plusieurs exemplaires.
     *
     * Avant de poser l'index, on fusionne les éventuels doublons existants
     * (cas rencontré en prod) pour que la contrainte puisse être appliquée.
     */
    public function up(): void
    {
        $this->mergeDuplicateCustomers();

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['user_id', 'email'], 'customers_user_id_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_user_id_email_unique');
        });
    }

    /**
     * Fusionne les fiches client en doublon (même coach + même email).
     *
     * Pour chaque groupe en doublon : on conserve une fiche de référence
     * (priorité à celle liée à un compte client, puis la plus ancienne), on
     * réaffecte les séances des fiches surnuméraires vers celle-ci, on récupère
     * le lien de compte client si besoin, puis on supprime les surnuméraires.
     */
    private function mergeDuplicateCustomers(): void
    {
        $groups = DB::table('customers')
            ->select('user_id', 'email')
            ->whereNotNull('email')
            ->groupBy('user_id', 'email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::transaction(function () use ($group) {
                $rows = DB::table('customers')
                    ->where('user_id', $group->user_id)
                    ->where('email', $group->email)
                    ->orderByRaw('account_user_id IS NULL') // fiches liées à un compte d'abord (NULL en dernier)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();

                $keeper = $rows->first();

                foreach ($rows->slice(1) as $loser) {
                    // Supprime les liens de séance déjà présents sur la fiche conservée
                    // (sinon violation de l'unique (session_id, customer_id) à la réaffectation).
                    $keeperSessionIds = DB::table('session_customer')
                        ->where('customer_id', $keeper->id)
                        ->pluck('session_id')
                        ->all();

                    DB::table('session_customer')
                        ->where('customer_id', $loser->id)
                        ->whereIn('session_id', $keeperSessionIds)
                        ->delete();

                    // Réaffecte les séances restantes vers la fiche conservée.
                    DB::table('session_customer')
                        ->where('customer_id', $loser->id)
                        ->update(['customer_id' => $keeper->id]);

                    // Récupère le lien de compte client si la fiche conservée n'en a pas.
                    if ($keeper->account_user_id === null && $loser->account_user_id !== null) {
                        DB::table('customers')
                            ->where('id', $keeper->id)
                            ->update(['account_user_id' => $loser->account_user_id]);
                        $keeper->account_user_id = $loser->account_user_id;
                    }

                    DB::table('customers')->where('id', $loser->id)->delete();
                }
            });
        }
    }
};
