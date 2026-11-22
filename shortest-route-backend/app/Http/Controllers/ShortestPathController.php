<?php

namespace App\Http\Controllers;

use App\Models\PathHistory;
use App\Services\GraphRepository;
use App\Services\ShortestPathService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShortestPathController extends Controller
{
    /** POST /api/shortest-path */
    public function calculate(Request $request, ShortestPathService $service, GraphRepository $graph): JsonResponse
    {
        $data = $request->validate([
            'fromNode' => ['required', 'string', 'exists:nodes,name'],
            'toNode' => ['required', 'string', 'exists:nodes,name', 'different:fromNode'],
        ]);

        // Cached per graph version: any node/connection change invalidates it automatically.
        $key = sprintf('shortest-path:%s:%s:%s', GraphRepository::version(), $data['fromNode'], $data['toNode']);

        $result = Cache::remember($key, now()->addHour(), fn () => $service->shortestPath(
            $data['fromNode'],
            $data['toNode'],
            $graph->adjacency(),
        ));

        PathHistory::create([
            'user_id' => $request->user()->id,
            'from_node' => $data['fromNode'],
            'to_node' => $data['toNode'],
            'node_names' => $result['nodeNames'],
            'distance' => $result['distance'],
        ]);

        // Unreachable => { "nodeNames": [], "distance": 0 } with HTTP 200.
        return response()->json($result);
    }

    /** GET /api/shortest-path/history (current user only) */
    public function history(Request $request): JsonResponse
    {
        $rows = $request->user()->pathHistories()
            ->latest()->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (PathHistory $h) => [
                'id' => $h->id,
                'fromNode' => $h->from_node,
                'toNode' => $h->to_node,
                'nodeNames' => $h->node_names,
                'distance' => $h->distance,
                'createdAt' => $h->created_at->toIso8601String(),
            ]);

        return response()->json($rows->values());
    }
}
