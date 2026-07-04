<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with two tenants so the multi-tenant
     * isolation is easy to see: each business has its own HikBridge key and
     * its own user, and neither can reach the other's devices.
     */
    public function run(): void
    {
        // Primary tenant — uses the real key from .env so the demo works
        // out of the box.
        $acme = Business::create([
            'name' => 'Acme Inc.',
            'hik_api_key' => config('hikbridge.api_key') ?: 'hbk_'.Str::random(48),
            'hik_webhook_secret' => config('hikbridge.webhook_secret'),
        ]);

        User::factory()->for($acme)->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Second tenant — placeholder key; swap in a real one to see isolation.
        $globex = Business::create([
            'name' => 'Globex Corp.',
            'hik_api_key' => 'hbk_'.Str::random(48),
        ]);

        User::factory()->for($globex)->create([
            'name' => 'Globex Admin',
            'email' => 'globex@example.com',
        ]);
    }
}
