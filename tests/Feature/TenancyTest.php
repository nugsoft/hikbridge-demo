<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Nugsoft\HikBridge\Facades\HikBridge;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sdk_calls_use_the_authenticated_users_business_key(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $keyA = $userA->business->hik_api_key;
        $keyB = $userB->business->hik_api_key;

        $this->assertNotSame($keyA, $keyB);

        // Business A's user hits the SDK — the request must carry key A.
        $this->actingAs($userA);
        HikBridge::devices()->list();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', "Bearer {$keyA}"));
        // ...and never key B: A cannot reach B's devices.
        Http::assertNotSent(fn ($request) => $request->hasHeader('Authorization', "Bearer {$keyB}"));

        // Simulate a fresh request for Business B's user: drop the per-request
        // (scoped) client and the cached facade instance, exactly as a new HTTP
        // request would in production.
        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstances();

        $this->actingAs($userB);
        HikBridge::devices()->list();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', "Bearer {$keyB}"));
    }

    public function test_guest_requests_fall_back_to_the_config_key(): void
    {
        config(['hikbridge.api_key' => 'hbk_globalfallback']);
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        // No authenticated user → no tenant → global config key.
        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstances();
        HikBridge::devices()->list();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer hbk_globalfallback'));
    }
}
