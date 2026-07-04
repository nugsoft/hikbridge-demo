<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Acme Inc.',
            'hik_api_key' => 'hbk_testkey1234567890',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('businesses', ['name' => 'Acme Inc.']);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->business_id);
        $this->assertSame('hbk_testkey1234567890', $user->business->hik_api_key);
    }

    public function test_registration_requires_a_business_and_api_key(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['business_name', 'hik_api_key']);
        $this->assertGuest();
    }

    public function test_registration_rejects_a_malformed_api_key(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Acme Inc.',
            'hik_api_key' => 'not-a-valid-key',
        ]);

        $response->assertSessionHasErrors('hik_api_key');
        $this->assertGuest();
    }
}
