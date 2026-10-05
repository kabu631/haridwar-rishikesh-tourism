<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the migrated website content.
     * Create an admin login afterwards with: php artisan app:create-admin you@example.com
     */
    public function run(): void
    {
        $this->call(ContentSeeder::class);
    }
}
