<?php

namespace Tests\Feature;

use App\Models\CirclePost;
use App\Models\Friendship;
use App\Models\StudyFocusParticipation;
use App\Models\StudyFocusRoom;
use App\Models\StudyGroup;
use App\Models\StudyGroupMember;
use App\Models\StudyRoom;
use App\Models\StudySession;
use App\Models\StudySubject;
use App\Models\User;
use App\Support\ActivityHeatmap;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MogStudyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('study-rooms.index'))
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

    public function test_user_can_create_study_group_and_becomes_owner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('study-groups.store'), [
            'name' => 'Medicina - UFMS',
            'description' => 'Grupo para vestibulandos de medicina.',
            'visibility' => 'public',
        ])->assertRedirect();

        $group = StudyGroup::query()->firstOrFail();

        $this->assertSame($user->id, $group->owner_id);
        $this->assertSame('Medicina - UFMS', $group->name);
        $this->assertSame(8, strlen($group->code));
        $this->assertDatabaseHas('study_group_members', [
            'study_group_id' => $group->id,
            'user_id' => $user->id,
            'role' => StudyGroupMember::ROLE_OWNER,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Grupos de estudo')
            ->assertSee(route('study-groups.index'), false);
    }

    public function test_dashboard_sidebar_uses_study_groups_instead_of_sessions_item(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Grupos de estudo')
            ->assertSee(route('study-groups.index'), false);

        $this->assertStringNotContainsString('href="#sessoes" class="sidebar-nav-item', $response->getContent());
    }

    public function test_user_can_create_password_private_group_and_password_is_hashed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('study-groups.store'), [
            'name' => 'Med UFMS privado',
            'description' => 'Grupo fechado com senha.',
            'visibility' => StudyGroup::VISIBILITY_PASSWORD,
            'password' => 'ufms2027',
        ])->assertRedirect();

        $group = StudyGroup::query()->firstOrFail();

        $this->assertSame(StudyGroup::VISIBILITY_PASSWORD, $group->visibility);
        $this->assertNotSame('ufms2027', $group->password_hash);
        $this->assertTrue(Hash::check('ufms2027', $group->password_hash));
    }

    public function test_study_group_search_finds_groups_by_name_and_code(): void
    {
        $owner = User::factory()->create();
        $searcher = User::factory()->create();
        $publicGroup = $this->createStudyGroupWithOwner($owner, [
            'name' => 'Med UFMS',
            'code' => 'MEDUFMS1',
        ]);
        $passwordGroup = $this->createStudyGroupWithOwner($owner, [
            'name' => 'ENEM fechado',
            'code' => 'ENEMPASS',
            'visibility' => StudyGroup::VISIBILITY_PASSWORD,
            'password_hash' => Hash::make('segredo'),
        ]);

        $this->actingAs($searcher)
            ->get(route('study-groups.index', ['group_search' => 'ufms']))
            ->assertOk()
            ->assertSeeText($publicGroup->name)
            ->assertSeeText($publicGroup->code);

        $this->actingAs($searcher)
            ->get(route('study-groups.index', ['group_search' => 'ENEMPASS']))
            ->assertOk()
            ->assertSeeText($passwordGroup->name)
            ->assertSeeText('Privado com senha');
    }

    public function test_password_private_group_requires_correct_password_to_join(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create();
        $group = $this->createStudyGroupWithOwner($owner, [
            'name' => 'Med UFMS privado',
            'code' => 'PASS2027',
            'visibility' => StudyGroup::VISIBILITY_PASSWORD,
            'password_hash' => Hash::make('ufms2027'),
        ]);

        $this->actingAs($guest)->post(route('study-groups.join', $group), [
            'password' => 'errada',
        ])->assertSessionHasErrors('password');

        $this->actingAs($guest)->post(route('study-groups.join', $group), [
            'password' => 'ufms2027',
        ])->assertRedirect(route('study-groups.show', $group));

        $this->assertDatabaseHas('study_group_members', [
            'study_group_id' => $group->id,
            'user_id' => $guest->id,
        ]);
    }

    public function test_user_can_join_public_study_group_by_code_once(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create();
        $group = StudyGroup::create([
            'owner_id' => $owner->id,
            'name' => 'ENEM 2027',
            'description' => 'Preparacao geral.',
            'visibility' => StudyGroup::VISIBILITY_PUBLIC,
            'code' => 'ABC12345',
            'status' => StudyGroup::STATUS_ACTIVE,
        ]);
        StudyGroupMember::create([
            'study_group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => StudyGroupMember::ROLE_OWNER,
            'joined_at' => now(),
        ]);

        $this->actingAs($guest)
            ->get(route('study-groups.show', $group))
            ->assertOk()
            ->assertSeeText('ENEM 2027');

        $this->actingAs($guest)
            ->post(route('study-groups.join-by-code'), ['code' => 'ABC12345'])
            ->assertRedirect(route('study-groups.show', $group));

        $this->actingAs($guest)
            ->post(route('study-groups.join', $group))
            ->assertRedirect(route('study-groups.show', $group));

        $this->assertDatabaseCount('study_group_members', 2);
        $this->assertDatabaseHas('study_group_members', [
            'study_group_id' => $group->id,
            'user_id' => $guest->id,
            'role' => StudyGroupMember::ROLE_MEMBER,
        ]);
    }

    public function test_friends_only_study_group_blocks_non_friends_and_allows_accepted_friends(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $outsider = User::factory()->create();
        $group = StudyGroup::create([
            'owner_id' => $owner->id,
            'name' => 'Grupo dos amigos',
            'description' => 'Foco fechado.',
            'visibility' => StudyGroup::VISIBILITY_FRIENDS,
            'code' => 'FRIEND01',
            'status' => StudyGroup::STATUS_ACTIVE,
        ]);
        StudyGroupMember::create([
            'study_group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => StudyGroupMember::ROLE_OWNER,
            'joined_at' => now(),
        ]);
        Friendship::create([
            'requester_id' => $owner->id,
            'addressee_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->actingAs($outsider)
            ->post(route('study-groups.join', $group))
            ->assertForbidden();

        $this->actingAs($friend)
            ->post(route('study-groups.join', $group))
            ->assertRedirect(route('study-groups.show', $group));

        $this->assertDatabaseHas('study_group_members', [
            'study_group_id' => $group->id,
            'user_id' => $friend->id,
        ]);
    }

    public function test_owner_can_create_focus_room_and_member_cannot(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = $this->createStudyGroupWithOwner($owner);
        StudyGroupMember::create([
            'study_group_id' => $group->id,
            'user_id' => $member->id,
            'role' => StudyGroupMember::ROLE_MEMBER,
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('study-groups.focus-rooms.store', $group), [
            'name' => 'Ciencias da Natureza',
            'description' => 'Biologia, fisica e quimica.',
            'icon' => 'book',
        ])->assertRedirect();

        $this->assertDatabaseHas('study_focus_rooms', [
            'study_group_id' => $group->id,
            'name' => 'Ciencias da Natureza',
            'position' => 1,
        ]);

        $this->actingAs($member)->post(route('study-groups.focus-rooms.store', $group), [
            'name' => 'Redacao',
            'icon' => 'notes',
        ])->assertForbidden();
    }

    public function test_study_group_page_expands_focus_room_inline_without_selected_room_panel(): void
    {
        $user = User::factory()->create();
        $group = $this->createStudyGroupWithOwner($user);
        $room = StudyFocusRoom::create([
            'study_group_id' => $group->id,
            'name' => 'Matematica',
            'description' => 'Canal coletivo para exatas.',
            'position' => 1,
            'is_active' => true,
        ]);
        StudySubject::create(['user_id' => $user->id, 'name' => 'Algebra']);

        $this->actingAs($user)
            ->get(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]))
            ->assertOk()
            ->assertDontSeeText('Sala selecionada')
            ->assertSeeText('Matematica')
            ->assertSeeText('Canal ativo')
            ->assertSee('is-expanded', false)
            ->assertSeeText('Materia pessoal')
            ->assertSeeText('Play / Iniciar estudo')
            ->assertSee(route('study-groups.focus-rooms.start', [$group, $room]), false);
    }

    public function test_study_group_page_renders_profile_photos_and_initial_fallbacks(): void
    {
        $user = User::factory()->create([
            'display_name' => 'Dev Foto',
            'profile_photo_path' => 'profile-photos/dev.png',
        ]);
        $memberWithoutPhoto = User::factory()->create([
            'display_name' => 'Sem Foto',
            'profile_photo_path' => null,
        ]);
        $group = $this->createStudyGroupWithOwner($user);
        StudyGroupMember::create([
            'study_group_id' => $group->id,
            'user_id' => $memberWithoutPhoto->id,
            'role' => StudyGroupMember::ROLE_MEMBER,
            'joined_at' => now(),
        ]);
        $room = StudyFocusRoom::create([
            'study_group_id' => $group->id,
            'name' => 'Matematica',
            'position' => 1,
            'is_active' => true,
        ]);
        $subject = StudySubject::create(['user_id' => $user->id, 'name' => 'Algebra']);
        $fallbackSubject = StudySubject::create(['user_id' => $memberWithoutPhoto->id, 'name' => 'Geometria']);
        $session = StudySession::create([
            'user_id' => $user->id,
            'study_subject_id' => $subject->id,
            'study_group_id' => $group->id,
            'study_focus_room_id' => $room->id,
            'subject' => $subject->name,
            'started_at' => now()->subMinute(),
        ]);
        $fallbackSession = StudySession::create([
            'user_id' => $memberWithoutPhoto->id,
            'study_subject_id' => $fallbackSubject->id,
            'study_group_id' => $group->id,
            'study_focus_room_id' => $room->id,
            'subject' => $fallbackSubject->name,
            'started_at' => now()->subMinute(),
        ]);
        StudyFocusParticipation::create([
            'study_focus_room_id' => $room->id,
            'study_session_id' => $session->id,
            'user_id' => $user->id,
            'study_subject_id' => $subject->id,
            'started_at' => now()->subMinute(),
            'status' => StudyFocusParticipation::STATUS_ACTIVE,
        ]);
        StudyFocusParticipation::create([
            'study_focus_room_id' => $room->id,
            'study_session_id' => $fallbackSession->id,
            'user_id' => $memberWithoutPhoto->id,
            'study_subject_id' => $fallbackSubject->id,
            'started_at' => now()->subMinute(),
            'status' => StudyFocusParticipation::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]))
            ->assertOk()
            ->assertSee('src="/storage/profile-photos/dev.png"', false)
            ->assertSee('alt="Foto de Dev Foto"', false)
            ->assertSee('<span class="friend-avatar">S</span>', false);
    }

    public function test_focus_room_show_route_redirects_to_group_with_selected_room(): void
    {
        $user = User::factory()->create();
        $group = $this->createStudyGroupWithOwner($user);
        $room = StudyFocusRoom::create([
            'study_group_id' => $group->id,
            'name' => 'Redacao',
            'position' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('study-groups.focus-rooms.show', [$group, $room]))
            ->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));
    }

    public function test_user_starts_focus_study_with_own_subject_and_stop_saves_study_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-20 10:00:00'));

        try {
            $user = User::factory()->create();
            $group = $this->createStudyGroupWithOwner($user);
            $room = StudyFocusRoom::create([
                'study_group_id' => $group->id,
                'name' => 'Ciencias da Natureza',
                'position' => 1,
                'is_active' => true,
            ]);
            $subject = StudySubject::create([
                'user_id' => $user->id,
                'name' => 'Biologia',
                'goal_period' => 'weekly',
                'goal_minutes' => 300,
            ]);

            $this->actingAs($user)->post(route('study-groups.focus-rooms.start', [$group, $room]), [
                'study_subject_id' => $subject->id,
                'notes' => 'Citologia',
            ])->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));

            $participation = StudyFocusParticipation::query()->firstOrFail();
            $session = StudySession::query()->firstOrFail();
            $this->assertSame($session->id, $participation->study_session_id);
            $this->assertSame($group->id, $session->study_group_id);
            $this->assertSame($room->id, $session->study_focus_room_id);
            $this->assertSame($subject->id, $session->study_subject_id);
            $this->assertNull($session->ended_at);

            Carbon::setTestNow(Carbon::parse('2026-07-20 10:25:00'));

            $this->actingAs($user)->post(route('study-groups.focus-rooms.stop', [$group, $room]))
                ->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));

            $this->assertSame(1500, $session->refresh()->duration_seconds);
            $this->assertNotNull($session->ended_at);
            $this->assertSame(1500, $participation->refresh()->duration_seconds);
            $this->assertSame(StudyFocusParticipation::STATUS_COMPLETED, $participation->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_focus_study_rejects_subject_from_another_user_and_second_active_session(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $group = $this->createStudyGroupWithOwner($user);
        $room = StudyFocusRoom::create([
            'study_group_id' => $group->id,
            'name' => 'Matematica',
            'position' => 1,
            'is_active' => true,
        ]);
        $ownSubject = StudySubject::create(['user_id' => $user->id, 'name' => 'Algebra']);
        $otherSubject = StudySubject::create(['user_id' => $other->id, 'name' => 'Fisica']);

        $this->actingAs($user)->post(route('study-groups.focus-rooms.start', [$group, $room]), [
            'study_subject_id' => $otherSubject->id,
        ])->assertSessionHasErrors('study_subject_id');

        $this->actingAs($user)->post(route('study-groups.focus-rooms.start', [$group, $room]), [
            'study_subject_id' => $ownSubject->id,
        ])->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));

        $this->actingAs($user)->post(route('study-groups.focus-rooms.start', [$group, $room]), [
            'study_subject_id' => $ownSubject->id,
        ])->assertSessionHasErrors('study_subject_id');
    }

    public function test_stopping_group_study_session_from_dashboard_closes_focus_participation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-20 11:00:00'));

        try {
            $user = User::factory()->create();
            $group = $this->createStudyGroupWithOwner($user);
            $room = StudyFocusRoom::create([
                'study_group_id' => $group->id,
                'name' => 'Redacao',
                'position' => 1,
                'is_active' => true,
            ]);
            $subject = StudySubject::create(['user_id' => $user->id, 'name' => 'Redacao']);

            $this->actingAs($user)->post(route('study-groups.focus-rooms.start', [$group, $room]), [
                'study_subject_id' => $subject->id,
            ])->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));

            $session = StudySession::query()->firstOrFail();
            $participation = StudyFocusParticipation::query()->firstOrFail();

            Carbon::setTestNow(Carbon::parse('2026-07-20 11:14:00'));

            $this->actingAs($user)->post(route('study-sessions.stop', $session))
                ->assertRedirect(route('dashboard'));

            $this->assertSame(840, $session->refresh()->duration_seconds);
            $this->assertSame(840, $participation->refresh()->duration_seconds);
            $this->assertSame(StudyFocusParticipation::STATUS_COMPLETED, $participation->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_paused_group_study_time_does_not_count_as_contribution(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-20 12:00:00'));

        try {
            $user = User::factory()->create();
            $group = $this->createStudyGroupWithOwner($user);
            $room = StudyFocusRoom::create([
                'study_group_id' => $group->id,
                'name' => 'Matematica',
                'position' => 1,
                'is_active' => true,
            ]);
            $subject = StudySubject::create(['user_id' => $user->id, 'name' => 'Algebra']);

            $this->actingAs($user)->post(route('study-groups.focus-rooms.start', [$group, $room]), [
                'study_subject_id' => $subject->id,
            ])->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));

            $session = StudySession::query()->firstOrFail();
            $participation = StudyFocusParticipation::query()->firstOrFail();

            Carbon::setTestNow(Carbon::parse('2026-07-20 12:10:00'));
            $this->actingAs($user)->post(route('study-sessions.pause', $session))
                ->assertRedirect();

            Carbon::setTestNow(Carbon::parse('2026-07-20 12:30:00'));
            $this->actingAs($user)->post(route('study-sessions.resume', $session))
                ->assertRedirect();

            Carbon::setTestNow(Carbon::parse('2026-07-20 12:45:00'));
            $this->actingAs($user)->post(route('study-groups.focus-rooms.stop', [$group, $room]))
                ->assertRedirect(route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]));

            $this->assertSame(1200, $session->refresh()->paused_seconds);
            $this->assertSame(1500, $session->duration_seconds);
            $this->assertSame(1200, $participation->refresh()->paused_seconds);
            $this->assertSame(1500, $participation->duration_seconds);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('25m hoje', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_legacy_study_room_routes_redirect_to_study_groups(): void
    {
        $user = User::factory()->create();
        $room = StudyRoom::create([
            'owner_id' => $user->id,
            'name' => 'Sala antiga',
            'subject' => 'Laravel',
            'visibility' => StudyRoom::VISIBILITY_PUBLIC,
            'code' => 'OLDROOM1',
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('study-rooms.index'))
            ->assertRedirect(route('study-groups.index'));

        $this->actingAs($user)
            ->get(route('study-rooms.show', $room))
            ->assertRedirect(route('study-groups.show', $room->code));
    }

    public function test_stopping_session_saves_only_current_session_duration_not_daily_accumulated_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $subject = StudySubject::create([
                'user_id' => $user->id,
                'name' => 'Laravel',
            ]);

            $this->createFinishedStudySession($user, '2026-07-18 08:00:00', 10, $subject);

            $session = StudySession::create([
                'user_id' => $user->id,
                'study_subject_id' => $subject->id,
                'subject' => 'Laravel',
                'started_at' => now()->subMinutes(5),
                'ended_at' => null,
                'duration_seconds' => 0,
            ]);

            $this->actingAs($user)->post(route('study-sessions.stop', $session))
                ->assertRedirect(route('dashboard'));

            $this->assertSame(300, $session->refresh()->duration_seconds);
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_dashboard_idle_timer_shows_zero_when_user_has_no_study_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Total estudado hoje')
                ->assertSeeText('00:00:00')
                ->assertDontSeeText('01:25:43');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_idle_timer_shows_total_finished_study_time_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();

            $this->createFinishedStudySession($user, '2026-07-18 09:00:00', 30);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Total estudado hoje')
                ->assertSeeText('00:30:00')
                ->assertSeeText('30m hoje')
                ->assertDontSeeText('30h hoje');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_today_summary_sums_finished_sessions_from_all_subjects(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $laravel = StudySubject::create(['user_id' => $user->id, 'name' => 'Laravel']);
            $java = StudySubject::create(['user_id' => $user->id, 'name' => 'Java']);

            $this->createFinishedStudySession($user, '2026-07-18 08:00:00', 30, $laravel);
            $this->createFinishedStudySession($user, '2026-07-18 10:00:00', 50, $java);
            $this->createFinishedStudySession($user, '2026-07-17 10:00:00', 120, $java);

            StudySession::create([
                'user_id' => $user->id,
                'study_subject_id' => $laravel->id,
                'subject' => 'Laravel',
                'started_at' => now()->subMinutes(20),
                'ended_at' => null,
                'duration_seconds' => 1200,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('1h20 hoje')
                ->assertSeeText('1h20')
                ->assertSeeText('Nenhuma meta semanal definida.')
                ->assertDontSeeText('de 6h');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_running_timer_shows_subject_daily_accumulated_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $laravel = StudySubject::create(['user_id' => $user->id, 'name' => 'Laravel']);
            $java = StudySubject::create(['user_id' => $user->id, 'name' => 'Java']);

            $this->createFinishedStudySession($user, '2026-07-18 08:00:00', 10, $laravel);
            $this->createFinishedStudySession($user, '2026-07-18 09:00:00', 40, $java);
            $this->createFinishedStudySession($user, '2026-07-17 09:00:00', 90, $laravel);

            StudySession::create([
                'user_id' => $user->id,
                'study_subject_id' => $laravel->id,
                'subject' => 'Laravel',
                'started_at' => now()->subMinutes(5),
                'ended_at' => null,
                'duration_seconds' => 0,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Laravel')
                ->assertSee('data-base-seconds="600"', false)
                ->assertSee('data-elapsed-seconds="900"', false)
                ->assertSeeText('00:15:00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_guest_is_redirected_from_study_subjects_page(): void
    {
        $this->get(route('study-subjects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_study_subjects_page_lists_only_authenticated_users_subjects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'description' => 'Framework PHP.',
        ]);

        StudySubject::create([
            'user_id' => $otherUser->id,
            'name' => 'Materia de outro usuario',
        ]);

        $this->actingAs($user)
            ->get(route('study-subjects.index'))
            ->assertOk()
            ->assertSeeText('Organize seus focos de estudo')
            ->assertSeeText('Laravel')
            ->assertSeeText('Framework PHP.')
            ->assertDontSeeText('Materia de outro usuario');
    }

    public function test_study_subjects_page_renders_edit_card_and_visual_photo_upload(): void
    {
        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'description' => 'Framework PHP.',
        ]);

        $this->actingAs($user)
            ->get(route('study-subjects.index'))
            ->assertOk()
            ->assertSee('aria-label="Editar Laravel"', false)
            ->assertSee('name="photo" accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('subject-photo-tile', false)
            ->assertSee(route('study-subjects.update', $subject), false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_study_subjects_page_renders_delete_button_for_subject(): void
    {
        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)
            ->get(route('study-subjects.index'))
            ->assertOk()
            ->assertSee('aria-label="Excluir Laravel"', false)
            ->assertSee(route('study-subjects.destroy', $subject), false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_study_subjects_page_can_create_full_subject_and_return_to_itself(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('study-subjects.store'), [
            'return_to' => 'subjects',
            'name' => 'Banco de Dados',
            'description' => 'SQL e modelagem.',
            'goal_value' => '90',
            'goal_unit' => 'minutes',
            'photo' => UploadedFile::fake()->image('database.png')->size(256),
        ])->assertRedirect(route('study-subjects.index'));

        $subject = StudySubject::query()->firstOrFail();

        $this->assertSame('Banco de Dados', $subject->name);
        $this->assertSame('SQL e modelagem.', $subject->description);
        $this->assertSame('weekly', $subject->goal_period);
        $this->assertSame(90, $subject->goal_minutes);
        $this->assertNotNull($subject->photo_path);
        Storage::disk('public')->assertExists($subject->photo_path);
    }

    public function test_dashboard_shows_only_three_recent_active_subjects(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $older = StudySubject::create(['user_id' => $user->id, 'name' => 'Antiga']);
            $createdRecent = StudySubject::create(['user_id' => $user->id, 'name' => 'Criada recente']);
            $editedRecent = StudySubject::create(['user_id' => $user->id, 'name' => 'Editada recente']);
            $studiedRecent = StudySubject::create(['user_id' => $user->id, 'name' => 'Estudada recente']);

            $older->forceFill(['created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)])->save();
            $createdRecent->forceFill(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)])->save();
            $editedRecent->forceFill(['created_at' => now()->subDays(7), 'updated_at' => now()->subHours(2)])->save();
            $studiedRecent->forceFill(['created_at' => now()->subDays(9), 'updated_at' => now()->subDays(9)])->save();

            StudySession::create([
                'user_id' => $user->id,
                'study_subject_id' => $studiedRecent->id,
                'subject' => 'Estudada recente',
                'started_at' => now()->subHour(),
                'ended_at' => now(),
                'duration_seconds' => 1800,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Criada recente')
                ->assertSeeText('Editada recente')
                ->assertSeeText('Estudada recente')
                ->assertDontSee('<strong>Antiga</strong>', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_subject_cards_render_edit_shortcut_to_subjects_page(): void
    {
        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Editar Laravel"', false)
            ->assertSee(route('study-subjects.index').'#materia-'.$subject->id, false);
    }

    public function test_study_subject_name_is_limited_to_50_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('study-subjects.store'), [
            'name' => str_repeat('a', 51),
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('study_subjects', 0);
    }

    public function test_user_can_update_study_subject_goal_and_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)->put(route('study-subjects.update', $subject), [
            'name' => 'Laravel Avancado',
            'description' => 'Arquitetura e testes.',
            'goal_value' => '1.5',
            'goal_unit' => 'hours',
            'photo' => UploadedFile::fake()->image('laravel.png')->size(512),
        ])->assertRedirect(route('dashboard'));

        $subject->refresh();

        $this->assertSame('Laravel Avancado', $subject->name);
        $this->assertSame('weekly', $subject->goal_period);
        $this->assertSame(90, $subject->goal_minutes);
        $this->assertNotNull($subject->photo_path);
        Storage::disk('public')->assertExists($subject->photo_path);
    }

    public function test_user_cannot_update_another_users_study_subject(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $owner->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($otherUser)->put(route('study-subjects.update', $subject), [
            'name' => 'Roubo de materia',
        ])->assertForbidden();

        $this->assertSame('Laravel', $subject->refresh()->name);
    }

    public function test_user_can_delete_own_study_subject_without_deleting_session_history(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'photo_path' => UploadedFile::fake()->image('laravel.png')->store('study-subjects', 'public'),
        ]);

        $session = $this->createFinishedStudySession($user, '2026-07-18 09:00:00', 30, $subject);

        $this->actingAs($user)
            ->delete(route('study-subjects.destroy', $subject))
            ->assertRedirect(route('study-subjects.index'));

        $this->assertDatabaseMissing('study_subjects', [
            'id' => $subject->id,
        ]);

        $session->refresh();
        $this->assertNull($session->study_subject_id);
        $this->assertSame('Laravel', $session->subject);
        Storage::disk('public')->assertMissing($subject->photo_path);
    }

    public function test_user_cannot_delete_another_users_study_subject(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $owner->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($otherUser)
            ->delete(route('study-subjects.destroy', $subject))
            ->assertForbidden();

        $this->assertDatabaseHas('study_subjects', [
            'id' => $subject->id,
        ]);
    }

    public function test_user_cannot_delete_subject_with_running_session(): void
    {
        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        StudySession::create([
            'user_id' => $user->id,
            'study_subject_id' => $subject->id,
            'subject' => 'Laravel',
            'started_at' => now()->subMinutes(10),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('study-subjects.destroy', $subject))
            ->assertRedirect(route('study-subjects.index'))
            ->assertSessionHasErrors('subject');

        $this->assertDatabaseHas('study_subjects', [
            'id' => $subject->id,
        ]);
    }

    public function test_study_subject_photo_must_respect_two_megabyte_limit(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $subject = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)->from(route('dashboard'))->put(route('study-subjects.update', $subject), [
            'name' => 'Laravel',
            'photo' => UploadedFile::fake()->image('pesada.png')->size(2049),
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('photo');

        $this->assertNull($subject->refresh()->photo_path);
    }

    public function test_study_subjects_page_only_renders_weekly_goal_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('study-subjects.index'))
            ->assertOk()
            ->assertSeeText('Meta semanal')
            ->assertDontSeeText('Periodo')
            ->assertDontSee('<option value="daily"', false)
            ->assertDontSee('<option value="weekly"', false);
    }

    public function test_subject_dashboard_progress_uses_weekly_goal_when_defined(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $subject = StudySubject::create([
                'user_id' => $user->id,
                'name' => 'Laravel',
                'goal_period' => 'weekly',
                'goal_minutes' => 120,
            ]);

            StudySession::create([
                'user_id' => $user->id,
                'study_subject_id' => $subject->id,
                'subject' => 'Laravel',
                'started_at' => now()->subHour(),
                'ended_at' => now(),
                'duration_seconds' => 3600,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Foco atual')
                ->assertSee('class="subject-row-head"', false)
                ->assertSeeText('Meta: 2h por semana')
                ->assertSeeText('Progresso 50%')
                ->assertSeeText('50%');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_does_not_render_projects_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Projetos')
            ->assertDontSeeText('MogStudy redesign')
            ->assertDontSeeText('Timer de foco')
            ->assertDontSeeText('Feed diario');
    }

    public function test_dashboard_weekly_focus_sums_subject_goals_and_week_progress(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $laravel = StudySubject::create([
                'user_id' => $user->id,
                'name' => 'Laravel',
                'goal_period' => 'weekly',
                'goal_minutes' => 120,
            ]);
            $sql = StudySubject::create([
                'user_id' => $user->id,
                'name' => 'SQL',
                'goal_period' => 'weekly',
                'goal_minutes' => 60,
            ]);
            StudySubject::create([
                'user_id' => $user->id,
                'name' => 'Sem meta',
            ]);

            $this->createFinishedStudySession($user, '2026-07-16 09:00:00', 45, $laravel);
            $this->createFinishedStudySession($user, '2026-07-18 10:00:00', 30, $sql);
            $this->createFinishedStudySession($user, '2026-07-09 10:00:00', 90, $sql);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Metas semanais')
                ->assertSeeText('1h15')
                ->assertSeeText('de 3h')
                ->assertSeeText('Faltam 1h45')
                ->assertSeeText('Soma das metas semanais de 2 materias.');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_weekly_focus_shows_empty_state_without_subject_goals(): void
    {
        $user = User::factory()->create();
        StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Nenhuma meta semanal definida.')
            ->assertDontSeeText('Faltam 6h');
    }

    public function test_daily_subject_goals_are_converted_to_weekly_goals(): void
    {
        $user = User::factory()->create();
        $daily = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Diaria antiga',
            'goal_period' => 'daily',
            'goal_minutes' => 120,
        ]);
        $weekly = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Semanal existente',
            'goal_period' => 'weekly',
            'goal_minutes' => 90,
        ]);
        $withoutGoal = StudySubject::create([
            'user_id' => $user->id,
            'name' => 'Sem meta',
        ]);

        $migration = require database_path('migrations/2026_07_19_000006_convert_daily_subject_goals_to_weekly.php');
        $migration->up();

        $this->assertDatabaseHas('study_subjects', [
            'id' => $daily->id,
            'goal_period' => 'weekly',
            'goal_minutes' => 840,
        ]);
        $this->assertDatabaseHas('study_subjects', [
            'id' => $weekly->id,
            'goal_period' => 'weekly',
            'goal_minutes' => 90,
        ]);
        $this->assertDatabaseHas('study_subjects', [
            'id' => $withoutGoal->id,
            'goal_period' => null,
            'goal_minutes' => null,
        ]);
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

    public function test_dashboard_profile_card_links_to_public_profile_without_readme_chip(): void
    {
        $user = User::factory()->create([
            'username' => 'studygirl',
            'profile_title' => 'dev',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('profile.show', $user).'"', false)
            ->assertSeeText('dev')
            ->assertDontSeeText('palavras no README');
    }

    public function test_user_can_save_daily_log_and_public_profile_renders_profile_fields_without_readme(): void
    {
        $user = User::factory()->create([
            'username' => 'studygirl',
            'profile_title' => 'Study Girl',
            'bio' => 'Hello from MogStudy.',
            'readme_markdown' => "# Legacy README\n\nThis should stay hidden.",
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
            ->assertSeeText('Study Girl')
            ->assertSeeText('@studygirl')
            ->assertSeeText('Hello from MogStudy.')
            ->assertDontSee('markdown-body', false)
            ->assertDontSeeText('Legacy README')
            ->assertDontSeeText('This should stay hidden.')
            ->assertDontSeeText('README');
    }

    public function test_user_can_update_profile_title_bio_and_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'profile_title' => null,
            'bio' => null,
        ]);

        $this->actingAs($user)->from(route('profile.show', $user))->put(route('profile.update'), [
            'profile_title' => 'dev',
            'bio' => 'Sem bio mockada agora.',
            'photo' => UploadedFile::fake()->image('avatar.png')->size(512),
        ])->assertRedirect(route('profile.show', $user))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('dev', $user->profile_title);
        $this->assertSame('Sem bio mockada agora.', $user->bio);
        $this->assertNotNull($user->profile_photo_path);
        $this->assertSame('/storage/'.$user->profile_photo_path, $user->profilePhotoUrl());
        Storage::disk('public')->assertExists($user->profile_photo_path);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('src="'.$user->profilePhotoUrl().'"', false);

        $this->get(route('profile.show', $user))
            ->assertOk()
            ->assertSee('src="'.$user->profilePhotoUrl().'"', false);
    }

    public function test_profile_title_and_bio_are_limited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('profile.show', $user))->put(route('profile.update'), [
            'profile_title' => str_repeat('a', 51),
            'bio' => str_repeat('b', 501),
        ])->assertRedirect(route('profile.show', $user))
            ->assertSessionHasErrors(['profile_title', 'bio']);
    }

    public function test_profile_photo_must_respect_two_megabyte_limit(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('profile.show', $user))->put(route('profile.update'), [
            'profile_title' => 'dev',
            'bio' => 'Bio valida.',
            'photo' => UploadedFile::fake()->image('pesada.png')->size(2049),
        ])->assertRedirect(route('profile.show', $user))
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_profile_photo_must_be_a_valid_image_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('profile.show', $user))->put(route('profile.update'), [
            'profile_title' => 'dev',
            'bio' => 'Bio valida.',
            'photo' => UploadedFile::fake()->create('avatar.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('profile.show', $user))
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_replacing_profile_photo_deletes_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'profile_photo_path' => UploadedFile::fake()->image('antiga.png')->store('profile-photos', 'public'),
        ]);
        $oldPhotoPath = $user->profile_photo_path;

        $this->actingAs($user)->from(route('profile.show', $user))->put(route('profile.update'), [
            'profile_title' => 'dev',
            'bio' => 'Bio atualizada.',
            'photo' => UploadedFile::fake()->image('nova.png')->size(256),
        ])->assertRedirect(route('profile.show', $user))
            ->assertSessionHasNoErrors();

        $user->refresh();

        Storage::disk('public')->assertMissing($oldPhotoPath);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->put(route('profile.update'), [
            'profile_title' => 'dev',
            'bio' => 'Bio.',
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_send_accept_and_remove_friendship(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();

        $this->actingAs($requester)
            ->post(route('friendships.store', $addressee))
            ->assertRedirect(route('dashboard'));

        $friendship = Friendship::query()->firstOrFail();
        $this->assertSame(Friendship::STATUS_PENDING, $friendship->status);

        $this->actingAs($addressee)
            ->post(route('friendships.accept', $friendship))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(Friendship::STATUS_ACCEPTED, $friendship->refresh()->status);

        $this->actingAs($requester)
            ->delete(route('friendships.destroy', $friendship))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);
    }

    public function test_user_cannot_friend_self_or_duplicate_friendship(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $this->actingAs($user)
            ->post(route('friendships.store', $user))
            ->assertStatus(422);

        $this->actingAs($user)
            ->post(route('friendships.store', $friend))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($friend)
            ->post(route('friendships.store', $user))
            ->assertStatus(422);
    }

    public function test_user_can_create_circle_post_and_limits_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('circle-posts.store'), [
            'title' => 'Passei de fase',
            'body' => 'Hoje finalizei meu bloco de Laravel.',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('circle_posts', [
            'user_id' => $user->id,
            'title' => 'Passei de fase',
        ]);

        $this->actingAs($user)->from(route('dashboard'))->post(route('circle-posts.store'), [
            'title' => str_repeat('a', 81),
            'body' => str_repeat('b', 201),
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['title', 'body']);
    }

    public function test_circle_reply_requires_cycle_access_and_respects_limit(): void
    {
        $author = User::factory()->create();
        $friend = User::factory()->create();
        $outsider = User::factory()->create();
        $post = CirclePost::create([
            'user_id' => $author->id,
            'title' => 'Foco da semana',
            'body' => 'Estudando bastante.',
        ]);
        Friendship::create([
            'requester_id' => $author->id,
            'addressee_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->actingAs($outsider)
            ->post(route('circle-posts.replies.store', $post), ['body' => 'Oi'])
            ->assertForbidden();

        $this->actingAs($friend)
            ->post(route('circle-posts.replies.store', $post), ['body' => str_repeat('x', 201)])
            ->assertRedirect()
            ->assertSessionHasErrors('body');

        $this->actingAs($friend)
            ->post(route('circle-posts.replies.store', $post), ['body' => 'Boa!'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('circle_post_replies', [
            'circle_post_id' => $post->id,
            'user_id' => $friend->id,
            'body' => 'Boa!',
        ]);
    }

    public function test_dashboard_circle_shows_posts_replies_friend_sessions_and_filters_outsiders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();
            $friend = User::factory()->create(['display_name' => 'Amiga Dev']);
            $outsider = User::factory()->create(['display_name' => 'Pessoa de Fora']);

            Friendship::create([
                'requester_id' => $user->id,
                'addressee_id' => $friend->id,
                'status' => Friendship::STATUS_ACCEPTED,
            ]);

            $post = CirclePost::create([
                'user_id' => $friend->id,
                'title' => 'Deploy estudado',
                'body' => 'Revisei filas e cache.',
            ]);
            $post->replies()->create([
                'user_id' => $user->id,
                'body' => 'Mandou bem!',
            ]);
            CirclePost::create([
                'user_id' => $outsider->id,
                'title' => 'Post invisivel',
                'body' => 'Nao deve aparecer.',
            ]);
            StudySession::create([
                'user_id' => $friend->id,
                'subject' => 'SQL',
                'started_at' => now()->subMinutes(10),
                'ended_at' => null,
                'duration_seconds' => 0,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Ciclo de estudos')
                ->assertSee('class="circle-feed circle-timeline"', false)
                ->assertSeeText('Publicacao')
                ->assertSeeText('Sessao de estudo')
                ->assertSeeText('Deploy estudado')
                ->assertSeeText('Revisei filas e cache.')
                ->assertSeeText('Mandou bem!')
                ->assertSeeText('Amiga Dev comecou a estudar SQL')
                ->assertDontSeeText('Post invisivel');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_circle_does_not_render_invites_or_suggestions_inside_panel(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create(['display_name' => 'Nova Amiga']);
        User::factory()->create(['display_name' => 'Sugestao Boa']);

        Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $user->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Ciclo de estudos')
            ->assertDontSeeText('Convites')
            ->assertDontSeeText('Sugestoes')
            ->assertDontSeeText('Sugestao Boa')
            ->assertDontSeeText('Adicionar');
    }

    public function test_friend_bell_shows_pending_requests_and_accepted_sent_notifications(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create(['display_name' => 'Nova Amiga']);
        $accepted = User::factory()->create(['display_name' => 'Amigo Aceito']);

        $pendingFriendship = Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $user->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
        Friendship::create([
            'requester_id' => $user->id,
            'addressee_id' => $accepted->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notificacoes de amizade"', false)
            ->assertSee('data-icon="bell"', false)
            ->assertSeeText('2')
            ->assertSeeText('Nova Amiga')
            ->assertSeeText('enviou um pedido de amizade')
            ->assertSee(route('friendships.accept', $pendingFriendship), false)
            ->assertSeeText('Amigo Aceito')
            ->assertSeeText('aceitou seu pedido de amizade');
    }

    public function test_dashboard_friend_search_finds_people_by_username_and_public_name(): void
    {
        $user = User::factory()->create([
            'username' => 'selfonly',
            'display_name' => 'Self Only',
        ]);
        $byUsername = User::factory()->create([
            'username' => 'coderana',
            'display_name' => 'Ana Dev',
            'email' => 'ana@example.com',
        ]);
        $byUsername->forceFill([
            'profile_photo_path' => 'profile-photos/ana.png',
        ])->save();
        $byName = User::factory()->create([
            'username' => 'zelda',
            'display_name' => 'Zelda Friend',
            'email' => 'zelda@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['friend_search' => '@coderana']))
            ->assertOk()
            ->assertSeeText('Encontrar amigos')
            ->assertSeeText('Ana Dev')
            ->assertSeeText('@'.$byUsername->username)
            ->assertSee(route('friendships.store', $byUsername), false)
            ->assertDontSeeText('ana@example.com');

        $this->actingAs($user)
            ->get(route('dashboard', ['friend_search' => 'Zelda Friend']))
            ->assertOk()
            ->assertSeeText('Zelda Friend')
            ->assertSeeText('@'.$byName->username)
            ->assertDontSeeText('zelda@example.com');

        $this->actingAs($user)
            ->get(route('dashboard', ['friend_search' => 'selfonly']))
            ->assertOk()
            ->assertSeeText('Nenhuma pessoa encontrada com essa busca.');
    }

    public function test_async_friend_search_returns_people_by_username_and_public_name_without_email(): void
    {
        $user = User::factory()->create([
            'username' => 'selfonly',
            'display_name' => 'Self Only',
        ]);
        $byUsername = User::factory()->create([
            'username' => 'coderana',
            'display_name' => 'Ana Dev',
            'email' => 'ana@example.com',
        ]);
        $byUsername->forceFill([
            'profile_photo_path' => 'profile-photos/ana.png',
        ])->save();
        $byName = User::factory()->create([
            'username' => 'zelda',
            'display_name' => 'Zelda Friend',
            'email' => 'zelda@example.com',
        ]);

        $this->actingAs($user)
            ->getJson(route('friend-search', ['friend_search' => '@coderana']))
            ->assertOk()
            ->assertJsonPath('has_search', true)
            ->assertJsonPath('results.0.username', $byUsername->username)
            ->assertJsonPath('results.0.display_name', 'Ana Dev')
            ->assertJsonPath('results.0.photo_url', '/storage/profile-photos/ana.png')
            ->assertJsonPath('results.0.friendship.state', 'none')
            ->assertJsonMissing(['email' => 'ana@example.com']);

        $this->actingAs($user)
            ->getJson(route('friend-search', ['friend_search' => 'Zelda Friend']))
            ->assertOk()
            ->assertJsonPath('results.0.username', $byName->username)
            ->assertJsonMissing(['email' => 'zelda@example.com']);

        $this->actingAs($user)
            ->getJson(route('friend-search', ['friend_search' => 'selfonly']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_dashboard_friend_search_shows_actions_by_friendship_state(): void
    {
        $user = User::factory()->create();
        $available = User::factory()->create(['username' => 'stateavailable', 'display_name' => 'State Available']);
        $sent = User::factory()->create(['username' => 'statesent', 'display_name' => 'State Sent']);
        $received = User::factory()->create(['username' => 'statereceived', 'display_name' => 'State Received']);
        $accepted = User::factory()->create(['username' => 'stateaccepted', 'display_name' => 'State Accepted']);

        $sentFriendship = Friendship::create([
            'requester_id' => $user->id,
            'addressee_id' => $sent->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
        $receivedFriendship = Friendship::create([
            'requester_id' => $received->id,
            'addressee_id' => $user->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
        $acceptedFriendship = Friendship::create([
            'requester_id' => $user->id,
            'addressee_id' => $accepted->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['friend_search' => 'State']))
            ->assertOk()
            ->assertSeeText('State Available')
            ->assertSee(route('friendships.store', $available), false)
            ->assertSeeText('State Sent')
            ->assertSeeText('Pedido enviado')
            ->assertSee(route('friendships.destroy', $sentFriendship), false)
            ->assertSeeText('State Received')
            ->assertSee(route('friendships.accept', $receivedFriendship), false)
            ->assertSeeText('State Accepted')
            ->assertSeeText('Amigos')
            ->assertSee(route('friendships.destroy', $acceptedFriendship), false);
    }

    public function test_async_friend_search_returns_friendship_states_and_requires_authentication(): void
    {
        $user = User::factory()->create();
        $available = User::factory()->create(['username' => 'stateavailable', 'display_name' => 'State Available']);
        $sent = User::factory()->create(['username' => 'statesent', 'display_name' => 'State Sent']);
        $received = User::factory()->create(['username' => 'statereceived', 'display_name' => 'State Received']);
        $accepted = User::factory()->create(['username' => 'stateaccepted', 'display_name' => 'State Accepted']);

        $sentFriendship = Friendship::create([
            'requester_id' => $user->id,
            'addressee_id' => $sent->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
        $receivedFriendship = Friendship::create([
            'requester_id' => $received->id,
            'addressee_id' => $user->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
        $acceptedFriendship = Friendship::create([
            'requester_id' => $user->id,
            'addressee_id' => $accepted->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('friend-search', ['friend_search' => 'State']))
            ->assertOk();

        $results = collect($response->json('results'))->keyBy('username');

        $this->assertSame('none', $results[$available->username]['friendship']['state']);
        $this->assertSame(route('friendships.store', $available), $results[$available->username]['friendship']['store_url']);
        $this->assertSame('sent', $results[$sent->username]['friendship']['state']);
        $this->assertSame(route('friendships.destroy', $sentFriendship), $results[$sent->username]['friendship']['destroy_url']);
        $this->assertSame('received', $results[$received->username]['friendship']['state']);
        $this->assertSame(route('friendships.accept', $receivedFriendship), $results[$received->username]['friendship']['accept_url']);
        $this->assertSame('accepted', $results[$accepted->username]['friendship']['state']);
        $this->assertSame(route('friendships.destroy', $acceptedFriendship), $results[$accepted->username]['friendship']['destroy_url']);

        auth()->logout();

        $this->getJson(route('friend-search', ['friend_search' => 'State']))
            ->assertRedirect(route('login'));
    }

    public function test_public_profile_renders_friendship_actions_by_state(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create(['username' => 'target']);

        $this->actingAs($viewer)
            ->get(route('profile.show', $target))
            ->assertOk()
            ->assertSeeText('Adicionar amigo')
            ->assertSee(route('friendships.store', $target), false);

        $sent = Friendship::create([
            'requester_id' => $viewer->id,
            'addressee_id' => $target->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $this->actingAs($viewer)
            ->get(route('profile.show', $target))
            ->assertOk()
            ->assertSeeText('Pedido enviado')
            ->assertSee(route('friendships.destroy', $sent), false);

        $sent->delete();
        $received = Friendship::create([
            'requester_id' => $target->id,
            'addressee_id' => $viewer->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $this->actingAs($viewer)
            ->get(route('profile.show', $target))
            ->assertOk()
            ->assertSeeText('Aceitar pedido')
            ->assertSee(route('friendships.accept', $received), false);

        $received->forceFill(['status' => Friendship::STATUS_ACCEPTED])->save();

        $this->actingAs($viewer)
            ->get(route('profile.show', $target))
            ->assertOk()
            ->assertSeeText('Amigos')
            ->assertSee(route('friendships.destroy', $received), false);

        $this->actingAs($viewer)
            ->get(route('profile.show', $viewer))
            ->assertOk()
            ->assertDontSeeText('Adicionar amigo')
            ->assertDontSeeText('Amigos');
    }

    public function test_activity_heatmap_uses_finished_study_sessions_for_green_levels(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();

            $this->createFinishedStudySession($user, '2026-07-13 09:00:00', 90);
            $this->createFinishedStudySession($user, '2026-07-14 09:00:00', 20);
            $this->createFinishedStudySession($user, '2026-07-15 09:00:00', 45);
            $this->createFinishedStudySession($user, '2026-07-16 09:00:00', 60);
            $this->createFinishedStudySession($user, '2026-07-17 09:00:00', 120);

            $heatmap = app(ActivityHeatmap::class)->build($user->id);
            $days = collect($heatmap['weeks'])->flatMap(fn ($week) => $week['days']);

            $this->assertSame('13/07/2026 - 1h30 estudados', $days->firstWhere('date', '2026-07-13')['label']);
            $this->assertSame(20, $days->firstWhere('date', '2026-07-14')['minutes']);
            $this->assertSame(1, $days->firstWhere('date', '2026-07-14')['level']);
            $this->assertSame(2, $days->firstWhere('date', '2026-07-15')['level']);
            $this->assertSame(3, $days->firstWhere('date', '2026-07-16')['level']);
            $this->assertSame(4, $days->firstWhere('date', '2026-07-17')['level']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_activity_heatmap_sums_sessions_and_ignores_running_sessions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();

            $this->createFinishedStudySession($user, '2026-07-18 08:00:00', 20);
            $this->createFinishedStudySession($user, '2026-07-18 10:00:00', 25);

            StudySession::create([
                'user_id' => $user->id,
                'subject' => 'Sessao aberta',
                'started_at' => now()->subMinutes(90),
                'ended_at' => null,
                'duration_seconds' => 5400,
            ]);

            $heatmap = app(ActivityHeatmap::class)->build($user->id);
            $days = collect($heatmap['weeks'])->flatMap(fn ($week) => $week['days']);
            $today = $days->firstWhere('date', '2026-07-18');

            $this->assertSame(45, $today['minutes']);
            $this->assertSame(2, $today['level']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_renders_yearly_activity_heatmap(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 12:00:00'));

        try {
            $user = User::factory()->create();

            $this->createFinishedStudySession($user, '2026-07-18 09:00:00', 120);
            $this->createFinishedStudySession($user, '2025-07-19 09:00:00', 30);

            $heatmap = app(ActivityHeatmap::class)->build($user->id);
            $days = collect($heatmap['weeks'])->flatMap(fn ($week) => $week['days']);

            $this->assertSame(365, $heatmap['total_days']);
            $this->assertGreaterThanOrEqual(365, $days->where('is_empty', false)->count());
            $this->assertSame(120, $days->firstWhere('date', '2026-07-18')['minutes']);
            $this->assertSame(4, $days->firstWhere('date', '2026-07-18')['level']);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Contribuicoes no ultimo ano')
                ->assertSee('heat-level-1', false)
                ->assertSee('18/07/2026 - 2h estudados', false)
                ->assertSee('heat-level-4', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createFinishedStudySession(User $user, string $startedAt, int $minutes, ?StudySubject $subject = null): StudySession
    {
        $startedAt = Carbon::parse($startedAt);

        return StudySession::create([
            'user_id' => $user->id,
            'study_subject_id' => $subject?->id,
            'subject' => $subject?->name ?? 'Laravel',
            'started_at' => $startedAt,
            'ended_at' => $startedAt->copy()->addMinutes($minutes),
            'duration_seconds' => $minutes * 60,
        ]);
    }

    private function createStudyGroupWithOwner(User $owner, array $overrides = []): StudyGroup
    {
        $group = StudyGroup::create(array_merge([
            'owner_id' => $owner->id,
            'name' => 'Medicina - UFMS',
            'description' => 'Grupo de estudos.',
            'visibility' => StudyGroup::VISIBILITY_PUBLIC,
            'status' => StudyGroup::STATUS_ACTIVE,
            'code' => 'GROUP'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
        ], $overrides));

        StudyGroupMember::create([
            'study_group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => StudyGroupMember::ROLE_OWNER,
            'joined_at' => now(),
        ]);

        return $group;
    }
}
