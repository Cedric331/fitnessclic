<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Exercise extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const MEDIA_IMAGE = 'exercise_image';

    public const MEDIA_DISK = 'public';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // All exercises are public by default
        static::creating(function (Exercise $exercise) {
            $exercise->is_shared = true;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'suggested_duration',
        'is_shared',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }

    /**
     * Relation with the creator user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation with categories (many-to-many)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Relation with training sessions (many-to-many with details)
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(Session::class, 'session_exercise', 'exercise_id', 'session_id')
            ->withPivot(['repetitions', 'rest_time', 'duration', 'additional_description', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }


    /**
     * Get the URL of the image of the exercise
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::MEDIA_IMAGE);
    }

    /**
     * Register the media collections for the exercise
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)->singleFile()->useDisk(self::MEDIA_DISK);
    }
}
