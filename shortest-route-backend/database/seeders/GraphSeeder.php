<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\Node;
use Illuminate\Database\Seeder;

/**
 * The example graph from the exam diagram (directed):
 *
 *   A->B 4   B->C 3   C->D 3
 *   B->E 2   A->E 7   A->F 6
 *   F->E 4   E->D 5
 *
 * Shortest A -> D = A, B, C, D (distance 10).
 */
class GraphSeeder extends Seeder
{
    public function run(): void
    {
        $nodes = [];
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $name) {
            $nodes[$name] = Node::firstOrCreate(['name' => $name]);
        }

        $edges = [
            ['A', 'B', 4], ['B', 'C', 3], ['C', 'D', 3],
            ['B', 'E', 2], ['A', 'E', 7], ['A', 'F', 6],
            ['F', 'E', 4], ['E', 'D', 5],
        ];

        foreach ($edges as [$from, $to, $distance]) {
            Connection::updateOrCreate(
                ['from_node_id' => $nodes[$from]->id, 'to_node_id' => $nodes[$to]->id],
                ['distance' => $distance],
            );
        }
    }
}
