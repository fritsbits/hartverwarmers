<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PreviewMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class PreviewModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Feature::define('diamant-goals', true);

        config()->set('preview.token', 'geheim-token');
    }

    public function test_preview_is_off_for_a_plain_visitor(): void
    {
        $this->get('/doelen/doen')->assertOk();

        $this->assertFalse(PreviewMode::doelenpagina());
    }

    public function test_the_secret_link_turns_preview_on_and_it_sticks(): void
    {
        $this->get('/doelen/doen?preview=geheim-token')->assertOk();

        $this->assertTrue(session()->get(PreviewMode::SESSION_KEY));

        $this->get('/doelen/doen')->assertOk();
        $this->assertTrue(session()->get(PreviewMode::SESSION_KEY));
    }

    public function test_a_wrong_token_does_nothing(): void
    {
        $this->get('/doelen/doen?preview=fout')->assertOk();

        $this->assertFalse(session()->has(PreviewMode::SESSION_KEY));
    }

    public function test_preview_off_removes_the_session_flag(): void
    {
        $this->withSession([PreviewMode::SESSION_KEY => true])
            ->get('/doelen/doen?preview=off')
            ->assertOk();

        $this->assertFalse(session()->has(PreviewMode::SESSION_KEY));
    }

    public function test_an_admin_sees_the_preview_without_the_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/doelen/doen')->assertOk();

        $this->assertTrue(PreviewMode::doelenpagina());
    }

    public function test_an_array_shaped_preview_parameter_does_not_error(): void
    {
        $this->get('/doelen/doen?preview[]=x')->assertOk();

        $this->assertFalse(session()->has(PreviewMode::SESSION_KEY));
    }

    public function test_an_array_shaped_off_parameter_does_not_error(): void
    {
        $this->withSession([PreviewMode::SESSION_KEY => true])
            ->get('/doelen/doen?preview[]=off')
            ->assertOk();
    }
}
