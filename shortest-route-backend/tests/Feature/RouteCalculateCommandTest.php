<?php

namespace Tests\Feature;

use Database\Seeders\GraphSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteCalculateCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GraphSeeder::class);
    }

    public function test_prints_path_and_total_distance(): void
    {
        $this->artisan('route:calculate', ['fromNode' => 'A', 'toNode' => 'D'])
            ->expectsOutput('A, B, C, D')
            ->expectsOutput('Total Distance: 10')
            ->assertExitCode(0);
    }

    public function test_reports_when_no_path_exists(): void
    {
        $this->artisan('route:calculate', ['fromNode' => 'E', 'toNode' => 'B'])
            ->expectsOutputToContain('No path exists')
            ->expectsOutput('Total Distance: 0')
            ->assertExitCode(0);
    }

    public function test_fails_for_unknown_node(): void
    {
        $this->artisan('route:calculate', ['fromNode' => 'A', 'toNode' => 'Z'])
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(1);
    }
}
