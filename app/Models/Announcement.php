<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Announcement extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const MEDIA_IMAGE = 'announcement_image';

    public const MEDIA_DISK = 'public';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
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
            'published_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Quand une annonce est activée, désactiver toutes les autres
        static::saving(function (Announcement $announcement) {
            if ($announcement->is_active && $announcement->isDirty('is_active')) {
                static::where('id', '!=', $announcement->id ?? 0)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                // Définir la date de publication si elle n'est pas définie
                if (! $announcement->published_at) {
                    $announcement->published_at = now();
                }
            }
        });
    }

    /**
     * Relation with users who have seen this announcement
     */
    public function seenByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user')
            ->withPivot('seen_at')
            ->withTimestamps();
    }

    /**
     * Check if a user has seen this announcement
     */
    public function hasBeenSeenBy(User $user): bool
    {
        return $this->seenByUsers()->where('user_id', $user->id)->exists();
    }

    /**
     * Mark as seen by a user
     */
    public function markAsSeenBy(User $user): void
    {
        if (! $this->hasBeenSeenBy($user)) {
            $this->seenByUsers()->attach($user->id, ['seen_at' => now()]);
        }
    }

    /**
     * Scope to get active announcements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the active announcement
     */
    public static function getActive(): ?self
    {
        return static::active()->first();
    }

    /**
     * Get the active announcement that the user hasn't seen
     */
    public static function getUnseenFor(User $user): ?self
    {
        return static::active()
            ->whereDoesntHave('seenByUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();
    }

    /**
     * Get the URL of the announcement image
     */
    public function getImageUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_IMAGE);

        return $url ?: null;
    }

    /**
     * Register the media collections for the announcement
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)
            ->singleFile()
            ->useDisk(self::MEDIA_DISK);
    }

    /**
     * Register media conversions to optimize images
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->performOnCollections(self::MEDIA_IMAGE)
            ->width(800)
            ->height(600)
            ->sharpen(10)
            ->quality(85)
            ->optimize()
            ->nonQueued();
    }
}

