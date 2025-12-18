<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\UserRole;

class StripeMigrateSubscription extends Command
{
    protected $signature = 'stripe:migrate-subscription
        {--customer= : Stripe customer id (cus_...)} cus_TbcgEJhmZbhnGV
        {--email= : Email du client (fallback si pas de cus_...)}
        {--user= : ID user local à rattacher (optionnel)}
        {--name=default : Nom Cashier de la subscription (default)}
        {--status=active,trialing : Statuts Stripe acceptés}
        {--dry-run : N\'écrit rien en base, affiche seulement}
    ';

    protected $description = 'Synchronise un abonnement Stripe existant dans la base locale (Cashier)';

    public function handle(): int
    {
        $secret = config('services.stripe.secret') ?? env('STRIPE_SECRET');

        if (!$secret) {
            $this->error('STRIPE_SECRET manquant (ou services.stripe.secret).');
            return self::FAILURE;
        }

        Stripe::setApiKey($secret);

        $dryRun = (bool) $this->option('dry-run');
        $subscriptionName = (string) $this->option('name');

        $allowedStatuses = collect(explode(',', (string) $this->option('status')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values();

        $customerId = $this->option('customer');

        if (!$customerId) {
            $email = (string) $this->option('email');

            if (!$email) {
                $this->error('Il faut fournir --customer=cus_... ou --email=...');
                return self::FAILURE;
            }

            $this->info("Recherche du customer Stripe par email: {$email}");

            $customers = Customer::all([
                'email' => $email,
                'limit' => 10,
            ]);

            if (count($customers->data) === 0) {
                $this->error("Aucun customer Stripe trouvé pour {$email}");
                return self::FAILURE;
            }

            if (count($customers->data) > 1) {
                $this->warn('Plusieurs customers trouvés pour cet email :');
                foreach ($customers->data as $c) {
                    $this->line("- {$c->id} | {$c->email} | name: ".($c->name ?? 'n/a'));
                }
                $this->error('Relance la commande avec --customer=cus_... pour lever l’ambiguïté.');
                return self::FAILURE;
            }

            $customerId = $customers->data[0]->id;
        }

        $customer = Customer::retrieve($customerId);

        $this->info("Customer Stripe: {$customer->id} | email: ".($customer->email ?? 'n/a'));

        $subs = Subscription::all([
            'customer' => $customer->id,
            'status' => 'all',
            'limit' => 20,
            'expand' => ['data.items.data.price'],
        ]);

        if (count($subs->data) === 0) {
            $this->error("Aucune subscription trouvée pour {$customer->id}");
            return self::FAILURE;
        }

        $subscription = collect($subs->data)
            ->first(fn (Subscription $s) => $allowedStatuses->contains($s->status));

        if (!$subscription) {
            $this->warn('Aucune subscription ne matche les statuts autorisés: '.$allowedStatuses->implode(', '));
            $this->info('Subscriptions trouvées :');
            foreach ($subs->data as $s) {
                $this->line("- {$s->id} | status={$s->status}");
            }
            return self::FAILURE;
        }

        $this->info("Subscription Stripe sélectionnée: {$subscription->id} | status={$subscription->status}");

        if (empty($subscription->items?->data)) {
            $this->error('Subscription sans items. Impossible de déterminer le price.');
            return self::FAILURE;
        }

        $firstItem = $subscription->items->data[0];
        $priceId = $firstItem->price->id ?? null;

        if (!$priceId) {
            $this->error('Impossible de lire price.id sur le 1er item.');
            return self::FAILURE;
        }

        $this->line("Price principal: {$priceId}");

        $userId = $this->option('user');

        if ($userId) {
            $user = User::query()->findOrFail((int) $userId);
        } else {
            if (!$customer->email) {
                $this->error('Customer Stripe sans email. Fournis --user=ID pour rattacher à un user existant.');
                return self::FAILURE;
            }

            $user = User::query()->firstOrCreate(
                ['email' => $customer->email],
                [
                    'name' => $customer->name ?? $customer->email,
                    'role' => UserRole::CUSTOMER->value,
                    'email_verified_at' => now(),
                    'stripe_id' => $customer->id,
                    'pm_type' => $customer->default_source,
                    'pm_last_four' => $customer->default_source,
                    'trial_ends_at' => $subscription->trial_end ? Carbon::createFromTimestamp((int) $subscription->trial_end) : null,
                    'ends_at' => $subscription->ended_at ? Carbon::createFromTimestamp((int) $subscription->ended_at) : null,
                    'password' => Hash::make(Str::random(10)),
                ]
            );
        }

        $this->info("User local: #{$user->id} | {$user->email}");

        $trialEndsAt = $subscription->trial_end ? Carbon::createFromTimestamp((int) $subscription->trial_end) : null;
        $endsAt = $subscription->ended_at ? Carbon::createFromTimestamp((int) $subscription->ended_at) : null;

        $payload = [
            'user_id' => $user->id,
            'name' => $subscriptionName,
            'stripe_id' => $subscription->id,
            'stripe_status' => $subscription->status,
            'stripe_price' => $priceId,
            'quantity' => (int) ($firstItem->quantity ?? 1),
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $endsAt,
        ];

        $this->line('---');
        $this->line('Résumé (local) :');
        foreach ($payload as $k => $v) {
            $this->line($k.': '.(is_object($v) ? $v->toDateTimeString() : (string) $v));
        }
        $this->line('---');

        if ($dryRun) {
            $this->warn('DRY RUN activé : aucune écriture en base.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($user, $customer, $subscription, $subscriptionName, $payload) {
            $user->forceFill([
                'stripe_id' => $customer->id,
            ])->save();

            $localSubscription = $user->subscriptions()
                ->updateOrCreate(
                    ['stripe_id' => $subscription->id],
                    [
                        'type' => $subscriptionName,
                        'stripe_status' => $payload['stripe_status'],
                        'stripe_price' => $payload['stripe_price'],
                        'quantity' => $payload['quantity'],
                        'trial_ends_at' => $payload['trial_ends_at'],
                        'ends_at' => $payload['ends_at'],
                    ]
                );

            // Upsert items
            foreach ($subscription->items->data as $item) {
                $localSubscription->items()->updateOrCreate(
                    ['stripe_id' => $item->id],
                    [
                        'stripe_product' => $item->price->product ?? null,
                        'stripe_price' => $item->price->id ?? null,
                        'quantity' => (int) ($item->quantity ?? 1),
                    ]
                );
            }
        });

        $this->info('OK ✅ Migration/synchronisation terminée.');

        $user->refresh();
        $this->line('Check Cashier: subscribed? '.($user->subscribed($subscriptionName) ? 'YES' : 'NO'));

        return self::SUCCESS;
    }
}