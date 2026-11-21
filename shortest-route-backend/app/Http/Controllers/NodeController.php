<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    /** GET /api/nodes */
    public function index(): JsonResponse
    {
        $nodes = Node::with('connections.toNode')->orderBy('name')->get();

        return response()->json($nodes->map(fn (Node $n) => $n->toApiArray())->values());
    }

    /** POST /api/nodes (admin only) */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only administrators can create nodes.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:nodes,name'],
            'connections' => ['sometimes', 'array'],
            // Targets must already exist; no self-loops; no duplicate targets.
            'connections.*.toNode' => ['required', 'string', 'distinct', 'exists:nodes,name', 'different:name'],
            'connections.*.distance' => ['required', 'integer', 'min:1'],
        ]);

        $node = DB::transaction(function () use ($data) {
            $node = Node::create(['name' => $data['name']]);

            $targets = Node::whereIn('name', collect($data['connections'] ?? [])->pluck('toNode'))
                ->pluck('id', 'name');

            foreach ($data['connections'] ?? [] as $c) {
                Connection::create([
                    'from_node_id' => $node->id,
                    'to_node_id' => $targets[$c['toNode']],
                    'distance' => $c['distance'],
                ]);
            }

            return $node->load('connections.toNode');
        });

        return response()->json([
            'message' => 'Node created',
            'node' => $node->toApiArray(),
        ], 201);
    }
}

