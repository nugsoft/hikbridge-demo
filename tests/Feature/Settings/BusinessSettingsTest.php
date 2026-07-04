<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_settings_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('business.edit'))
            ->assertOk()
            ->assertSee('Business');
    }

    public function test_a_user_can_update_their_business_name_and_key(): void
    {
        $user = User::factory()->create();
        $originalKey = $user->business->hik_api_key;

        Livewire::actingAs($user)
            ->test('pages::settings.business')
            ->set('name', 'Renamed Co.')
            ->set('hik_api_key', 'hbk_brandnewkey1234567890')
            ->call('updateBusiness')
            ->assertHasNoErrors();

        $business = $user->business->fresh();
        $this->assertSame('Renamed Co.', $business->name);
        $this->assertSame('hbk_brandnewkey1234567890', $business->hik_api_key);
        $this->assertNotSame($originalKey, $business->hik_api_key);
    }

    public function test_leaving_the_key_blank_keeps_the_existing_key(): void
    {
        $user = User::factory()->create();
        $originalKey = $user->business->hik_api_key;

        Livewire::actingAs($user)
            ->test('pages::settings.business')
            ->set('name', 'Still Same Key Co.')
            ->set('hik_api_key', '')
            ->call('updateBusiness')
            ->assertHasNoErrors();

        $this->assertSame($originalKey, $user->business->fresh()->hik_api_key);
    }

    public function test_a_malformed_key_is_rejected(): void
    {
        $user = User::factory()->create();
        $originalKey = $user->business->hik_api_key;

        Livewire::actingAs($user)
            ->test('pages::settings.business')
            ->set('hik_api_key', 'not-a-valid-key')
            ->call('updateBusiness')
            ->assertHasErrors('hik_api_key');

        $this->assertSame($originalKey, $user->business->fresh()->hik_api_key);
    }
}
