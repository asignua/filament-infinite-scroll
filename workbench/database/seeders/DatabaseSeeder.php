<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Post;
use Workbench\App\Models\Team;
use Workbench\App\Models\User;

/**
 * Demo data for `vendor/bin/testbench workbench:build`; log in at /admin as emma@example.com / password.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'emma@example.com'], ['name' => 'Emma Carter', 'password' => 'password', 'is_active' => true]);

        $team = Team::query()->create(['name' => 'Acme Supply']);

        for ($i = 1; $i <= 60; $i++) {
            Post::query()->create(['team_id' => $team->id, 'title' => "Post {$i}", 'status' => $i % 3 === 0 ? 'published' : 'draft']);
        }
    }
}
