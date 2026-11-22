<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GraphRepository;
use App\Services\ShortestPathService;

class CalculateRoute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:calculate {fromNode : Name of the start node, e.g. A} {toNode : Name of the destination node, e.g. D}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the shortest route between two nodes (Dijkstra, directed graph)';

    /**
     * Execute the console command.
     */
    public function handle(ShortestPathService $service, GraphRepository $graph): int
    {
        $from = (string) $this->argument('fromNode');
        $to = (string) $this->argument('toNode');

        $names = $graph->nodeNames();

        foreach ([$from, $to] as $name) {
            if (! in_array($name, $names, true)) {
                $this->error("Node \"{$name}\" does not exist in the graph.");

                return self::FAILURE;
            }
        }

        $result = $service->shortestPath($from, $to, $graph->adjacency());

        if ($result['nodeNames'] === []) {
            $this->warn("No path exists from {$from} to {$to}.");
            $this->line('Total Distance: 0');

            return self::SUCCESS;
        }

        $this->line(implode(', ', $result['nodeNames']));
        $this->line('Total Distance: '.$result['distance']);

        return self::SUCCESS;
    }
}
