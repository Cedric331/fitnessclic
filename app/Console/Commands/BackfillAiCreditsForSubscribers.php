<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillAiCreditsForSubscribers extends Command
{
    protected $signature = 'ai:credits:backfill
        {--dry-run : N\'écrit rien en base, affiche seulement ce qui serait fait}
        {--chunk=200 : Taille des lots pour éviter d\'exploser la RAM}
        {--force : Créditer même si l\'utilisateur a déjà un historique de crédits}
        {--only-user= : Ne traiter qu\'un user id (debug)}
    ';

    protected $description = 'Attribue les crédits IA aux comptes déjà abonnés (rattrapage post-release).';

    public function handle(): int
    {
        $creditLimit = (int) config('services.openai.credit_limit', 10);

        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $force = (bool) $this->option('force');
        $onlyUserId = $this->option('only-user');

        $this->info(sprintf(
            "Backfill crédits IA | credit_limit=%d | dry-run=%s | chunk=%d | force=%s",
            $creditLimit,
            $dryRun ? 'yes' : 'no',
            $chunk,
            $force ? 'yes' : 'no',
        ));

        $query = User::query()
            ->whereNotNull('stripe_id')
            ->when($onlyUserId, fn ($q) => $q->whereKey($onlyUserId));


        $processed = 0;
        $credited = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById($chunk, function ($users) use (
            $dryRun,
            $force,
            $creditLimit,
            &$processed,
            &$credited,
            &$skipped
        ) {
            foreach ($users as $user) {
                $processed++;

                if (! $user->subscribed('default')) {
                    $skipped++;
                    continue;
                }

                $hasAnyCreditHistory = $user->credits()->exists();
                if (! $force && $hasAnyCreditHistory) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("DRY-RUN: créditer user#{$user->id} ({$user->email}) de {$creditLimit} crédits.");
                    $credited++;
                    continue;
                }

                DB::transaction(function () use ($user, $creditLimit) {
                    $user->initializeAiCredits();

                    Log::info('Backfill crédits IA', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'credits' => $creditLimit,
                    ]);
                });

                $this->info("OK: user#{$user->id} ({$user->email}) crédité ({$creditLimit}).");
                $credited++;
            }
        });

        $this->newLine();
        $this->info("Terminé. processed={$processed} | credited={$credited} | skipped={$skipped}");

        return self::SUCCESS;
    }
}
