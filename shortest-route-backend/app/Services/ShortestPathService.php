<?php

namespace App\Services;

use InvalidArgumentException;
use SplPriorityQueue;

/**
 * Dijkstra's shortest path over a DIRECTED graph with positive edge weights.
 *
 * Graph format (adjacency map): node name => [ neighbour name => distance ]
 *
 *   [
 *       'A' => ['B' => 4, 'E' => 7, 'F' => 6],
 *       'B' => ['C' => 3, 'E' => 2],
 *       ...
 *   ]
 *
 * Edges are only followed in the stated direction: 'B' => ['E' => 2] does NOT
 * mean E can reach B.
 */
class ShortestPathService
{
    /**
     * @param  array<string, array<string, int|float>>  $graphNodes
     * @return array{nodeNames: string[], distance: int|float}
     *         ['nodeNames' => [], 'distance' => 0] when no path exists.
     */
    public function shortestPath(string $fromNodeName, string $toNodeName, array $graphNodes): array
    {
        $none = ['nodeNames' => [], 'distance' => 0];

        $known = $this->knownNodes($graphNodes);
        if (! isset($known[$fromNodeName]) || ! isset($known[$toNodeName])) {
            return $none;
        }

        /** @var array<string, int|float> $dist best known distance from the source */
        $dist = [$fromNodeName => 0];
        /** @var array<string, string> $prev predecessor on the best known path */
        $prev = [];

        // SplPriorityQueue is a max-heap, so we insert negative distances to get min-first.
        $queue = new SplPriorityQueue();
        $queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $queue->insert($fromNodeName, 0);

        while (! $queue->isEmpty()) {
            $item = $queue->extract();
            $current = (string) $item['data'];
            $currentDist = -$item['priority'];

            // Stale queue entry: a shorter route to $current was already processed.
            if ($currentDist > ($dist[$current] ?? INF)) {
                continue;
            }

            // First time the target is popped its distance is final (positive weights).
            if ($current === $toNodeName) {
                break;
            }

            foreach ($graphNodes[$current] ?? [] as $neighbour => $weight) {
                $neighbour = (string) $neighbour;

                if (! is_numeric($weight) || $weight <= 0) {
                    throw new InvalidArgumentException(
                        "Edge {$current} -> {$neighbour} must have a positive distance."
                    );
                }

                $candidate = $currentDist + $weight;

                if ($candidate < ($dist[$neighbour] ?? INF)) {
                    $dist[$neighbour] = $candidate;
                    $prev[$neighbour] = $current;
                    $queue->insert($neighbour, -$candidate);
                }
            }
        }

        if (! isset($dist[$toNodeName])) {
            return $none; // target never reached
        }

        // Walk predecessors back from the target to rebuild the route.
        $path = [];
        for ($node = $toNodeName; $node !== null; $node = $prev[$node] ?? null) {
            $path[] = (string) $node;
        }

        return [
            'nodeNames' => array_reverse($path),
            'distance' => $dist[$toNodeName],
        ];
    }

    /** @return array<string, true> every node that appears as a source or a target */
    private function knownNodes(array $graph): array
    {
        $known = [];
        foreach ($graph as $name => $edges) {
            $known[(string) $name] = true;
            foreach ($edges as $to => $_) {
                $known[(string) $to] = true;
            }
        }

        return $known;
    }
}
