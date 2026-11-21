<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{

    protected $fillable = [
        'from_node_id',
        'to_node_id',
        'distance'
    ];

    protected function casts(): array
    {
        return ['distance' => 'integer'];
    }

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'from_node_id');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'to_node_id');
    }
}
