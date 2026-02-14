<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Popin extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const MEDIA_IMAGE = 'popin_image';

    public const MEDIA_DISK = 'public';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'promo_code',
        'delay_seconds',
        'is_active',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'delay_seconds' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // When a popin is activated, deactivate all others
        static::saving(function (Popin $popin) {
            if ($popin->is_active && $popin->isDirty('is_active')) {
                static::where('id', '!=', $popin->id ?? 0)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                if (! $popin->published_at) {
                    $popin->published_at = now();
                }
            }
        });
    }

    /**
     * Relation with prospects.
     */
    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    /**
     * Scope to get active popins.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the URL of the popin image.
     */
    public function getImageUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_IMAGE);

        return $url ?: null;
    }

    /**
     * Register the media collections for the popin.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)
            ->singleFile()
            ->useDisk(self::MEDIA_DISK);
    }

    /**
     * Register media conversions to optimize images.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->performOnCollections(self::MEDIA_IMAGE)
            ->width(900)
            ->height(600)
            ->sharpen(10)
            ->quality(85)
            ->optimize()
            ->nonQueued();
    }
}

