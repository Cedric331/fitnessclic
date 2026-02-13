<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BlogPost extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const MEDIA_BANNER = 'blog_banner';

    public const MEDIA_DISK = 'public';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'tags',
        'published_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BlogPost $post) {
            if (! $post->slug || $post->isDirty('title')) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }

            if ($post->is_published && ! $post->published_at) {
                $post->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        // timezone is Paris
        $now = now()->setTimezone('Europe/Paris');
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query) use ($now) {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', $now);
            });
    }

    public function getBannerUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_BANNER);

        return $url ?: null;
    }

    public function getExcerptAttribute(): string
    {
        $content = trim(strip_tags((string) $this->content));

        return Str::limit($content, 160, '...');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_BANNER)
            ->singleFile()
            ->useDisk(self::MEDIA_DISK);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->performOnCollections(self::MEDIA_BANNER)
            ->width(1400)
            ->height(800)
            ->sharpen(10)
            ->quality(85)
            ->optimize()
            ->nonQueued();
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
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


