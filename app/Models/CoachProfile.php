<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected $fillable = [
        'user_id',
        'slug',
        'headline',
        'bio',
        'hourly_rate',
        'city',
        'postal_code',
        'specialties',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'hourly_rate' => 'integer',
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
