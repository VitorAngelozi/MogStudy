<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MogStudyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_register_login_and_logout(): void
    {
        $registerResponse = $this->post(route('register.store'), [
            'username' => 'novousuario',
            'display_name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'bio' => 'Estudando todos os dias.',
        ]);

        $registerResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'novousuario',
            'email' => 'novo@example.com',
        ]);

        $this->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();

        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_start_and_stop_study_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('study-sessions.store'), [
            'subject' => 'Laravel',
            'notes' => 'Autenticação e rotas.',
        ])->assertRedirect(route('dashboard'));

        $session = StudySession::query()->firstOrFail();

        $this->actingAs($user)->post(route('study-sessions.stop', $session))
            ->assertRedirect(route('dashboard'));

        $session->refresh();

        $this->assertNotNull($session->ended_at);
        $this->assertGreaterThanOrEqual(1, $session->duration_seconds);
    }

    public function test_user_can_save_daily_log_and_public_profile_renders_readme(): void
    {
        $user = User::factory()->create([
            'username' => 'studygirl',
            'readme_markdown' => "# Study Girl\n\nHello from MogStudy.",
        ]);

        $this->actingAs($user)->post(route('daily-logs.store'), [
            'title' => 'Dia produtivo',
            'content' => 'Terminei um bloco de estudo em Laravel.',
            'log_date' => now()->toDateString(),
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('daily_logs', [
            'user_id' => $user->id,
            'title' => 'Dia produtivo',
        ]);

        $this->get(route('profile.show', $user))
            ->assertOk()
            ->assertSeeText('Hello from MogStudy.');
    }
}
