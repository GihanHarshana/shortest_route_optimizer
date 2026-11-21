<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(GraphSeeder::class);

        // Admin (can POST /api/nodes): admin@example.com / password
        User::firstOrNew(['email' => 'admin@example.com'])
            ->forceFill(['name' => 'Admin', 'password' => 'password', 'is_admin' => true])
            ->save();

        // Regular user: user@example.com / password
        User::firstOrNew(['email' => 'user@example.com'])
            ->forceFill(['name' => 'Demo User', 'password' => 'password', 'is_admin' => false])
            ->save();
    }
}
