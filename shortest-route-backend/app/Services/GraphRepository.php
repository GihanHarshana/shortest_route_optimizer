<?php

namespace App\Services;

use App\Models\Node;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Loads the graph from the database in the adjacency format
 * expected by ShortestPathService, and owns the cache "version" used
 * to invalidate cached shortest-path results when the graph changes.
 */
class GraphRepository
{
    private const VERSION_KEY = 'graph:version';

    /** @return array<string, array<string, int>> */
    public function adjacency(): array
    {
        $graph = [];

        Node::with('connections.toNode')->orderBy('name')->get()->each(function (Node $node) use (&$graph) {
            $graph[$node->name] = $node->connections
                ->mapWithKeys(fn ($c) => [$c->toNode->name => $c->distance])
                ->all();
        });

        return $graph;
    }

    /** @return string[] */
    public function nodeNames(): array
    {
        return Node::orderBy('name')->pluck('name')->all();
    }

    public static function version(): string
    {
        return Cache::rememberForever(self::VERSION_KEY, fn () => (string) Str::uuid());
    }

}
