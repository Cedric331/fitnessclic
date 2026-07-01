<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CoachProfile extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const MEDIA_AVATAR = 'coach_avatar';

    public const MEDIA_DISK = 'public';

    /**
     * Date limite d'inscription (incluse) pour bénéficier du statut « Fondateur ».
     */
    public const FOUNDER_CUTOFF = '2026-08-31';

    protected $fillable = [
        'user_id',
        'slug',
        'headline',
        'bio',
        'hourly_rate',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'specialties',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'hourly_rate' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CoachProfile $profile) {
            if (! $profile->slug) {
                $base = $profile->user?->name ?: 'coach';
                $profile->slug = $profile->generateUniqueSlug($base);
            }

            if ($profile->is_published && ! $profile->published_at) {
                $profile->published_at = now();
            }
        });
    }

    /**
     * Relation with the owner user (coach).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Only published profiles (publicly visible).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Restrict to profiles within $radiusKm of the given coordinates,
     * exposing a `distance` (km) attribute and ordering by proximity.
     * Formule de Haversine (6371 = rayon terrestre en km).
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $haversine = '(6371 * acos('
            .'cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) '
            .'+ sin(radians(?)) * sin(radians(latitude))'
            .'))';

        return $query
            ->select('coach_profiles.*')
            ->selectRaw("{$haversine} as distance", [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $radiusKm])
            ->orderBy('distance');
    }

    /**
     * Présentation « carte » du coach, partagée entre l'annuaire et la home.
     * `distance` n'est présent qu'après une recherche de proximité (scopeNearby).
     *
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->user?->name,
            'headline' => $this->headline,
            'hourly_rate' => $this->hourly_rate_euros,
            'city' => $this->city,
            'distance_km' => isset($this->distance) ? round((float) $this->distance) : null,
            'specialties' => array_slice($this->specialties ?? [], 0, 3),
            'avatar_url' => $this->avatar_url,
            'is_founder' => $this->is_founder,
        ];
    }

    /**
     * Statut « Fondateur » : réservé aux coachs dont le compte a été créé au
     * plus tard le 31 août 2026 inclus. On se base sur la date d'inscription
     * de l'utilisateur, la fiche coach étant créée paresseusement plus tard.
     */
    public function getIsFounderAttribute(): bool
    {
        $registeredAt = $this->user?->created_at;

        return $registeredAt !== null
            && $registeredAt->lessThanOrEqualTo(Carbon::parse(self::FOUNDER_CUTOFF)->endOfDay());
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_AVATAR, 'optimized')
            ?: $this->getFirstMediaUrl(self::MEDIA_AVATAR);

        return $url ?: ($this->user?->avatar_url ?: null);
    }

    /**
     * Hourly rate in euros (from stored cents), or null.
     */
    public function getHourlyRateEurosAttribute(): ?float
    {
        return $this->hourly_rate === null ? null : round($this->hourly_rate / 100, 2);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_AVATAR)
            ->singleFile()
            ->useDisk(self::MEDIA_DISK);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->performOnCollections(self::MEDIA_AVATAR)
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->quality(85)
            ->optimize()
            ->nonQueued();
    }

    private function generateUniqueSlug(string $base): string
    {
        $baseSlug = Str::slug($base) ?: 'coach';
        $slug = $baseSlug;
        $counter = 1;

        while (
            static::where('slug', $slug)
                ->when($this->exists, fn ($query) => $query->where('id', '!=', $this->id))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
