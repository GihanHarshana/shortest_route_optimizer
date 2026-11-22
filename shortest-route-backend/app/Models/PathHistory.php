<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PathHistory extends Model
{
    protected $fillable = [
        'user_id',
        'from_node',
        'to_node',
        'node_names',
        'distance'

    ];

    protected function casts(): array
    {
        return [
            'node_names' => 'array',
            'distance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
