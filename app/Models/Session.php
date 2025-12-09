<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Session extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'training_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'notes',
        'session_date',
        'share_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
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
     * Relation with multiple customers (many-to-many)
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'session_customer', 'session_id', 'customer_id')
            ->withTimestamps();
    }

    /**
     * Relation with exercises (many-to-many with details)
     * @deprecated Use sessionExercises() instead for better control
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'session_exercise', 'session_id', 'exercise_id')
            ->withPivot(['repetitions', 'rest_time', 'duration', 'weight', 'additional_description', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Relation with session exercises (direct relation with session_exercise table)
     */
    public function sessionExercises(): HasMany
    {
        return $this->hasMany(SessionExercise::class, 'session_id')->orderBy('order');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->share_token)) {
                $session->share_token = (string) Str::uuid();
            }
        });
    }
}
