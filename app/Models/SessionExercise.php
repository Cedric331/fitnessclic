<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionExercise extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'session_exercise';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'exercise_id',
        'custom_exercise_name',
        'repetitions',
        'rest_time',
        'duration',
        'weight',
        'use_duration',
        'use_bodyweight',
        'additional_description',
        'order',
        'sets_count',
        // Nouveaux champs pour Super 7
        'block_id',
        'block_type',
        'position_in_block',
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
     * Relation with session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Relation with exercise
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Relation with sets (séries multiples)
     */
    public function sets(): HasMany
    {
        return $this->hasMany(SessionExerciseSet::class, 'session_exercise_id')->orderBy('order');
    }

    /**
     * Vérifier si l'exercice est en mode Super Set
     */
    public function isSet(): bool
    {
        return $this->block_type === 'set' && $this->block_id !== null;
    }

    /**
     * @deprecated Use isSet() instead
     */
    public function isSuper7(): bool
    {
        return $this->isSet();
    }

    /**
     * Relation avec les autres exercices du même bloc
     */
    public function blockExercises()
    {
        if (!$this->block_id) {
            return collect([]);
        }
        
        return self::where('block_id', $this->block_id)
            ->where('id', '!=', $this->id)
            ->orderBy('position_in_block')
            ->get();
    }
}

