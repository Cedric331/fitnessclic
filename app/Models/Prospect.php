<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospect extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'popin_id',
        'email',
        'promo_code_sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'promo_code_sent_at' => 'datetime',
        ];
    }

    /**
     * Relation with popin.
     */
    public function popin(): BelongsTo
    {
        return $this->belongsTo(Popin::class);
    }
}

