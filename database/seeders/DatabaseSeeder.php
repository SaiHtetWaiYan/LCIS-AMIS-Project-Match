<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        User::factory()->lcis()->create(['name' => 'LCIS User', 'email' => 'lcis@example.com']);
        User::factory()->amis()->create(['name' => 'AMIS User', 'email' => 'amis@example.com']);
    }
}
