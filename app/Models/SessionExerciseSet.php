<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionExerciseSet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_exercise_id',
        'set_number',
        'repetitions',
        'weight',
        'rest_time',
        'duration',
        'use_duration',
        'use_bodyweight',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'use_duration' => 'boolean',
            'use_bodyweight' => 'boolean',
        ];
    }

    /**
     * Relation with session_exercise
     */
    public function sessionExercise(): BelongsTo
    {
        return $this->belongsTo(SessionExercise::class);
    }
}

