<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'user_id',
    ];

    /**
     * Relation with the creator user (null for public categories)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation with exercises (many-to-many)
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class);
    }

    /**
     * Scope for public categories
     */
    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    /**
     * Scope for private categories
     */
    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    /**
     * Scope for categories available to a specific user
     */
    public function scopeForUser($query, User $user, ?int $teamId = null)
    {
        $teamIds = $teamId ? collect([$teamId]) : $user->teams()->pluck('teams.id');

        return $query->where(function ($q) use ($user, $teamIds) {
            $q->where('type', 'public')
                ->orWhere(function ($q2) use ($user, $teamIds) {
                    $q2->where('type', 'private')
                        ->where(function ($q3) use ($user, $teamIds) {
                            $q3->where('user_id', $user->id);

                            if ($teamIds->isNotEmpty()) {
                                $q3->orWhereIn('user_id', function ($sub) use ($teamIds) {
                                    $sub->select('user_id')
                                        ->from('team_user')
                                        ->whereIn('team_id', $teamIds);
                                });
                            }
                        });
                });
        });
    }
}
