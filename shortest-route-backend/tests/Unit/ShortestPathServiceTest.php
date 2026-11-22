<?php

namespace Tests\Unit;

use App\Services\ShortestPathService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ShortestPathServiceTest extends TestCase
{
    /** The directed graph from the exam diagram. */
    private function graph(): array
    {
        return [
            'A' => ['B' => 4, 'E' => 7, 'F' => 6],
            'B' => ['C' => 3, 'E' => 2],
            'C' => ['D' => 3],
            'D' => [],
            'E' => ['D' => 5],
            'F' => ['E' => 4],
        ];
    }

    public function test_finds_shortest_path_a_to_d(): void
    {
        $result = (new ShortestPathService())->shortestPath('A', 'D', $this->graph());

        $this->assertSame(['A', 'B', 'C', 'D'], $result['nodeNames']);
        $this->assertSame(10, $result['distance']);
    }

    public function test_prefers_cheaper_indirect_route_over_direct_edge(): void
    {
        // A->E direct costs 7, but A->B->E costs 6.
        $result = (new ShortestPathService())->shortestPath('A', 'E', $this->graph());

        $this->assertSame(['A', 'B', 'E'], $result['nodeNames']);
        $this->assertSame(6, $result['distance']);
    }

    public function test_unreachable_nodes_return_empty_path_and_zero_distance(): void
    {
        // Edges are directed: B -> E exists, E -> B does not.
        $result = (new ShortestPathService())->shortestPath('E', 'B', $this->graph());

        $this->assertSame(['nodeNames' => [], 'distance' => 0], $result);
    }

    public function test_respects_edge_direction(): void
    {
        $service = new ShortestPathService();

        $this->assertSame(2, $service->shortestPath('B', 'E', $this->graph())['distance']);
        $this->assertSame([], $service->shortestPath('E', 'B', $this->graph())['nodeNames']);
    }

    public function test_unknown_node_returns_empty_result(): void
    {
        $result = (new ShortestPathService())->shortestPath('A', 'Z', $this->graph());

        $this->assertSame(['nodeNames' => [], 'distance' => 0], $result);
    }

    public function test_same_start_and_end_returns_single_node(): void
    {
        $result = (new ShortestPathService())->shortestPath('C', 'C', $this->graph());

        $this->assertSame(['nodeNames' => ['C'], 'distance' => 0], $result);
    }

    public function test_handles_cycles(): void
    {
        $graph = ['A' => ['B' => 1], 'B' => ['A' => 1, 'C' => 1], 'C' => []];

        $result = (new ShortestPathService())->shortestPath('A', 'C', $graph);

        $this->assertSame(['A', 'B', 'C'], $result['nodeNames']);
        $this->assertSame(2, $result['distance']);
    }

    public function test_rejects_non_positive_distances(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ShortestPathService())->shortestPath('A', 'B', ['A' => ['B' => 0], 'B' => []]);
    }
}
