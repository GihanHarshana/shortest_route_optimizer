<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\GraphSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShortestPathApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GraphSeeder::class);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/shortest-path', ['fromNode' => 'A', 'toNode' => 'D'])
            ->assertUnauthorized();
    }

    public function test_valid_request_returns_path_and_distance_and_records_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/shortest-path', ['fromNode' => 'A', 'toNode' => 'D'])
            ->assertOk()
            ->assertExactJson(['nodeNames' => ['A', 'B', 'C', 'D'], 'distance' => 10]);

        $this->assertDatabaseHas('path_histories', [
            'user_id' => $user->id,
            'from_node' => 'A',
            'to_node' => 'D',
            'distance' => 10,
        ]);
    }

    public function test_unreachable_destination_returns_empty_path(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/shortest-path', ['fromNode' => 'E', 'toNode' => 'B'])
            ->assertOk()
            ->assertExactJson(['nodeNames' => [], 'distance' => 0]);
    }

    public function test_invalid_input_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        // missing fields
        $this->postJson('/api/shortest-path', [])
            ->assertStatus(422)->assertJsonValidationErrors(['fromNode', 'toNode']);

        // unknown node
        $this->postJson('/api/shortest-path', ['fromNode' => 'A', 'toNode' => 'Z'])
            ->assertStatus(422)->assertJsonValidationErrors(['toNode']);

        // same node
        $this->postJson('/api/shortest-path', ['fromNode' => 'A', 'toNode' => 'A'])
            ->assertStatus(422)->assertJsonValidationErrors(['toNode']);
    }

    public function test_history_is_scoped_to_the_authenticated_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Sanctum::actingAs($alice);
        $this->postJson('/api/shortest-path', ['fromNode' => 'A', 'toNode' => 'D'])->assertOk();

        Sanctum::actingAs($bob);
        $this->postJson('/api/shortest-path', ['fromNode' => 'B', 'toNode' => 'E'])->assertOk();

        $response = $this->getJson('/api/shortest-path/history')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.fromNode', 'B');
        $response->assertJsonPath('0.nodeNames', ['B', 'E']);
        $response->assertJsonPath('0.distance', 2);
    }
}
