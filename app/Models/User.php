<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (User $user) {
            try {
                if ($user->hasStripeId() && $user->subscribed('default')) {
                    $user->subscription('default')->cancelNow();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Erreur lors de l\'annulation de l\'abonnement Stripe lors de la suppression du compte', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'avatar_url',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * Check if the user can access the admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the avatar URL for the user
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    /**
     * Get the name for the user
     */
    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if the user is a customer
     */
    public function isClient(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    /**
     * Relation with customers managed by this user
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Relation with categories created by this user (private)
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relation with exercises created by this user
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    /**
     * Relation with training sessions created by this user
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Check if the user has a customer
     */
    public function hasCustomer(Customer $customer): bool
    {
        return $this->customers()->where('id', $customer->id)->exists();
    }

    /**
     * Check if the user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default');
    }

    /**
     * Check if the user is on a Pro subscription
     */
    public function isPro(): bool
    {
        return $this->hasActiveSubscription();
    }

    /**
     * Check if the user is on a free account (not Pro)
     */
    public function isFree(): bool
    {
        return ! $this->isPro();
    }

    /**
     * Check if the user can save sessions
     */
    public function canSaveSessions(): bool
    {
        return $this->isPro();
    }

    /**
     * Check if the user can have unlimited clients
     */
    public function canHaveUnlimitedClients(): bool
    {
        return $this->isPro();
    }

    /**
     * Check if the user can export PDFs
     */
    public function canExportPdf(): bool
    {
        return $this->isPro();
    }

    /**
     * Check if the user can import unlimited exercises
     */
    public function canImportUnlimitedExercises(): bool
    {
        return $this->isPro();
    }

    /**
     * Check if the user can create unlimited categories
     */
    public function canCreateUnlimitedCategories(): bool
    {
        return $this->isPro();
    }

    /**
     * Get the subscription plan name
     */
    public function getSubscriptionPlanName(): string
    {
        if ($this->hasActiveSubscription()) {
            return 'FitnessClic Pro';
        }

        return 'Gratuit';
    }
}
