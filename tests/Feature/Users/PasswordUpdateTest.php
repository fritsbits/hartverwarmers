<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_current_password_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_mismatched_passwords_return_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_successful_password_update_flashes_success(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('toast', fn (array $toast) => $toast['heading'] === 'Wachtwoord bijgewerkt'
            && $toast['variant'] === 'success'
        );
    }

    public function test_security_page_renders_error_after_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profiel/beveiliging')
            ->followingRedirects()
            ->put('/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk();
        $response->assertSee('Het wachtwoord is onjuist.');
    }

    public function test_security_page_renders_error_after_mismatched_passwords(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profiel/beveiliging')
            ->followingRedirects()
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ]);

        $response->assertOk();
        $response->assertSee('Wachtwoord komt niet overeen met de bevestiging.');
    }
}
