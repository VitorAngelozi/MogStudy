<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\StudySession;
use App\Models\StudySubject;
use App\Models\User;
use App\Support\ActivityHeatmap;
use Carbon\Carbon;
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

    public function test_registration_rejects_invalid_username_with_translated_message(): void
    {
        $response = $this->followingRedirects()->from(route('register'))->post(route('register.store'), [
            'username' => 'nome com espaco',
            'display_name' => 'Novo Usuário',
            'email' => 'nome-invalido@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'bio' => 'Estudando todos os dias.',
        ]);

        $response->assertSeeText('Revise os campos abaixo.');
        $response->assertSeeText('O campo nome de usuário deve conter apenas letras, números, hífens e sublinhados.');
        $this->assertGuest();
    }

    public function test_user_can_start_and_stop_study_session(): void
    {
        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)->post(route('study-sessions.store'), [
            'study_subject_id' => $subject->id,
            'study_subject_name' => 'Laravel',
            'notes' => 'Autenticação e rotas.',
        ])->assertRedirect(route('dashboard'));

        $session = StudySession::query()->firstOrFail();
        $this->assertSame('Laravel', $session->subject);
        $this->assertNotNull($session->study_subject_id);

        $this->actingAs($user)->post(route('study-sessions.stop', $session))
            ->assertRedirect(route('dashboard'));

        $session->refresh();

        $this->assertNotNull($session->ended_at);
        $this->assertGreaterThanOrEqual(1, $session->duration_seconds);
    }

    public function test_user_can_create_study_subject_and_dashboard_uses_real_subjects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('study-subjects.store'), [
            'name' => 'Matematica',
            'description' => 'Exercicios e revisao.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('study_subjects', [
            'user_id' => $user->id,
            'name' => 'Matematica',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Matematica')
            ->assertSeeText('0m estudadas')
            ->assertDontSeeText('Ingles');
    }

    public function test_user_cannot_create_duplicate_study_subject_name(): void
    {
        $user = User::factory()->create();
        StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)->from(route('dashboard'))->post(route('study-subjects.store'), [
            'name' => ' Laravel ',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('study_subjects', 1);
    }

    public function test_user_cannot_start_session_without_registered_subject(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('study-sessions.store'), [
            'study_subject_name' => 'Materia inexistente',
            'notes' => 'Tentativa sem cadastro.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('study_sessions', 0);
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

    public function test_dashboard_renders_yearly_activity_heatmap(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();

            DailyLog::create([
                'user_id' => $user->id,
                'log_date' => '2026-07-18',
                'title' => 'Dia forte',
                'content' => 'Fechei um bloco grande.',
                'study_minutes' => 240,
            ]);

            DailyLog::create([
                'user_id' => $user->id,
                'log_date' => '2025-07-19',
                'title' => 'Primeiro dia',
                'content' => 'Comecei o registro anual.',
                'study_minutes' => 30,
            ]);

            $heatmap = app(ActivityHeatmap::class)->build($user->id);
            $days = collect($heatmap['weeks'])->flatMap(fn ($week) => $week['days']);

            $this->assertSame(365, $heatmap['total_days']);
            $this->assertGreaterThanOrEqual(365, $days->where('is_empty', false)->count());
            $this->assertSame(240, $days->firstWhere('date', '2026-07-18')['minutes']);
            $this->assertSame(4, $days->firstWhere('date', '2026-07-18')['level']);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Contribuicoes no ultimo ano')
                ->assertSee('18/07/2026 - 240 min', false)
                ->assertSee('heat-level-4', false);
        } finally {
            Carbon::setTestNow();
        }
    }
}
