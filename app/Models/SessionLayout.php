<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionLayout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'layout_data',
        'canvas_width',
        'canvas_height',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'layout_data' => 'array',
            'canvas_width' => 'integer',
            'canvas_height' => 'integer',
        ];
    }

    /**
     * Relation with session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}

