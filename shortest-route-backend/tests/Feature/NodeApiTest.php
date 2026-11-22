<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\GraphSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_nodes_with_connections(): void
    {
        $this->seed(GraphSeeder::class);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/nodes')->assertOk()->assertJsonCount(6);

        $response->assertJsonPath('0.name', 'A');
        $response->assertJsonFragment(['toNode' => 'B', 'distance' => 4]);
    }

    public function test_admin_can_create_node_with_connections(): void
    {
        $this->seed(GraphSeeder::class);
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->postJson('/api/nodes', [
            'name' => 'G',
            'connections' => [['toNode' => 'D', 'distance' => 2]],
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Node created')
            ->assertJsonPath('node.name', 'G')
            ->assertJsonPath('node.connections.0.toNode', 'D');

        $this->assertDatabaseHas('nodes', ['name' => 'G']);
    }

    public function test_non_admin_cannot_create_nodes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/nodes', ['name' => 'G'])->assertForbidden();
    }

    public function test_node_creation_validation(): void
    {
        $this->seed(GraphSeeder::class);
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        // duplicate name
        $this->postJson('/api/nodes', ['name' => 'A'])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);

        // unknown target, non-positive distance
        $this->postJson('/api/nodes', ['name' => 'G', 'connections' => [['toNode' => 'Z', 'distance' => 3]]])
            ->assertStatus(422)->assertJsonValidationErrors(['connections.0.toNode']);

        $this->postJson('/api/nodes', ['name' => 'G', 'connections' => [['toNode' => 'A', 'distance' => 0]]])
            ->assertStatus(422)->assertJsonValidationErrors(['connections.0.distance']);

        // self loop
        $this->postJson('/api/nodes', ['name' => 'G', 'connections' => [['toNode' => 'G', 'distance' => 1]]])
            ->assertStatus(422);
    }
}
