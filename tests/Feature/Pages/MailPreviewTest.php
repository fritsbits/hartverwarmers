<?php

namespace Tests\Feature\Pages;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.mails'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.mails'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_mails_index(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->create();
        Fiche::factory()->for(User::factory())->for($initiative)->create(['published' => true]);

        $response = $this->actingAs($admin)->get(route('admin.mails'));

        $response->assertStatus(200);
        $response->assertSee('E-mailverificatie');
        $response->assertSee('Wachtwoord resetten');
        $response->assertSee('Welkomstmail');
    }

    public function test_admin_can_preview_fiche_comment_email(): void
    {
        $admin = User::factory()->admin()->create();
        $initiative = Initiative::factory()->create();
        Fiche::factory()->for(User::factory())->for($initiative)->create(['published' => true]);

        $response = $this->actingAs($admin)->get(route('admin.mails.preview', 'fiche-comment'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_admin_can_preview_verify_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.preview', 'verify-email'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_admin_can_preview_reset_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.preview', 'reset-password'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_admin_can_preview_welcome_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.preview', 'welcome'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_admin_can_view_individual_mail(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.show', 'verify-email'));

        $response->assertStatus(200);
        $response->assertSee('E-mailverificatie');
    }

    public function test_unknown_mail_show_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.show', 'non-existent'));

        $response->assertStatus(404);
    }

    public function test_unknown_email_type_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mails.preview', 'non-existent'));

        $response->assertStatus(404);
    }
}
