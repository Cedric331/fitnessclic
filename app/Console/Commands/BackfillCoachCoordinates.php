<?php

namespace App\Console\Commands;

use App\Models\CoachProfile;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class BackfillCoachCoordinates extends Command
{
    protected $signature = 'coaches:backfill-coordinates
        {--dry-run : N\'écrit rien en base, affiche seulement ce qui serait fait}
        {--chunk=100 : Taille des lots pour éviter d\'exploser la RAM}
        {--force : Re-géocoder même les profils qui ont déjà des coordonnées}
    ';

    protected $description = 'Géocode la ville des profils coachs existants (API Géo gouv.fr) pour activer la recherche aux alentours.';

    public function handle(GeocodingService $geocoder): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $force = (bool) $this->option('force');

        $this->info(sprintf(
            'Backfill coordonnées coachs | dry-run=%s | chunk=%d | force=%s',
            $dryRun ? 'yes' : 'no',
            $chunk,
            $force ? 'yes' : 'no',
        ));

        $query = CoachProfile::query()
            // On ne touche qu'aux profils ayant une ville renseignée.
            ->whereNotNull('city')
            ->where('city', '!=', '')
            // Sans --force : seulement ceux qui n'ont pas encore de coordonnées.
            ->when(! $force, fn ($q) => $q->where(fn ($sub) => $sub
                ->whereNull('latitude')
                ->orWhereNull('longitude')));

        $total = (clone $query)->count();
        $noCity = CoachProfile::query()
            ->where(fn ($q) => $q->whereNull('city')->orWhere('city', ''))
            ->count();

        if ($noCity > 0) {
            $this->line("Ignorés (sans ville renseignée) : {$noCity} profil(s).");
        }

        if ($total === 0) {
            $this->info('Aucun profil à géocoder.');

            return self::SUCCESS;
        }

        $this->info("Profils à traiter : {$total}.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $geocoded = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById($chunk, function ($profiles) use (
            $geocoder,
            $dryRun,
            $bar,
            &$processed,
            &$geocoded,
            &$failed,
        ) {
            foreach ($profiles as $profile) {
                $processed++;

                $coordinates = $geocoder->geocodeCity($profile->city, $profile->postal_code);

                if ($coordinates === null) {
                    $failed++;
                    $this->newLine();
                    $this->warn("✗ Géocodage introuvable : profil#{$profile->id} « {$profile->city} » ({$profile->postal_code}).");
                    $bar->advance();

                    continue;
                }

                if (! $dryRun) {
                    $profile->forceFill([
                        'latitude' => $coordinates['lat'],
                        'longitude' => $coordinates['lng'],
                    ])->saveQuietly();
                }

                $geocoded++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Terminé. traités={$processed} | géocodés={$geocoded} | échecs={$failed} | sans ville={$noCity}");

        return self::SUCCESS;
    }
}
