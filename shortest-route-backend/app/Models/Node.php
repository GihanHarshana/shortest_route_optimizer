<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{

    protected $fillable = ['name'];

    /** Outgoing (directed) edges of this node. */
    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class, 'from_node_id');
    }

    /** Shape used by the API: { id, name, connections: [{ toNode, distance }] } */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'connections' => $this->connections
                ->map(fn (Connection $c) => [
                    'toNode' => $c->toNode->name,
                    'distance' => $c->distance,
                ])
                ->values()
                ->all(),
        ];
    }
}
