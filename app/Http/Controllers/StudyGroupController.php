<?php

namespace App\Http\Controllers;

use App\Actions\StudyGroups\CreateFocusRoomAction;
use App\Actions\StudyGroups\CreateStudyGroupAction;
use App\Actions\StudyGroups\JoinStudyGroupAction;
use App\Actions\StudyGroups\StartFocusStudyAction;
use App\Actions\StudyGroups\StopFocusStudyAction;
use App\Http\Requests\StudyGroups\StartFocusStudyRequest;
use App\Http\Requests\StudyGroups\StoreFocusRoomRequest;
use App\Http\Requests\StudyGroups\StoreStudyGroupRequest;
use App\Http\Requests\StudyGroups\UpdateStudyGroupRequest;
use App\Models\StudyFocusParticipation;
use App\Models\StudyFocusRoom;
use App\Models\StudyGroup;
use App\Models\StudyGroupMember;
use App\Models\StudySubject;
use App\Services\StudyGroups\FocusRoomRankingService;
use App\Services\StudyGroups\StudyGroupRankingService;
use App\Services\StudyGroups\StudyGroupStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudyGroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $joinCode = trim((string) $request->query('code', ''));
        $search = trim((string) $request->query('group_search', ''));

        $groups = StudyGroup::query()
            ->with(['owner'])
            ->withCount(['members', 'focusRooms'])
            ->whereHas('members', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->get();

        $publicGroups = StudyGroup::query()
            ->withCount(['members', 'focusRooms'])
            ->where('visibility', StudyGroup::VISIBILITY_PUBLIC)
            ->where('status', StudyGroup::STATUS_ACTIVE)
            ->whereDoesntHave('members', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(6)
            ->get();

        $searchResults = $search === ''
            ? collect()
            : $this->searchGroups($user, $search);

        $activeParticipation = $user->studyFocusParticipations()
            ->with(['focusRoom.group', 'studySubject'])
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->latest('started_at')
            ->first();

        return view('study_groups.index', [
            'groups' => $groups,
            'publicGroups' => $publicGroups,
            'joinCode' => $joinCode,
            'search' => $search,
            'searchResults' => $searchResults,
            'activeParticipation' => $activeParticipation,
        ]);
    }

    public function create()
    {
        return view('study_groups.create');
    }

    public function store(StoreStudyGroupRequest $request, CreateStudyGroupAction $action)
    {
        $group = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('study-groups.show', $group)
            ->with('status', 'Grupo de estudo criado.');
    }

    public function show(Request $request, StudyGroup $studyGroup, StudyGroupStatisticsService $statistics, StudyGroupRankingService $ranking, FocusRoomRankingService $roomRanking)
    {
        Gate::authorize('view', $studyGroup);

        $studyGroup->load([
            'owner',
            'members.user',
            'focusRooms' => fn ($query) => $query
                ->with(['activeParticipations.user', 'activeParticipations.studySubject'])
                ->orderBy('position'),
        ]);

        $membership = $this->membership($studyGroup, $request->user());
        $activeParticipation = $request->user()->studyFocusParticipations()
            ->with(['focusRoom.group', 'studySubject'])
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->latest('started_at')
            ->first();
        $selectedRoom = $this->selectedRoom($studyGroup, $request, $activeParticipation);

        if ($selectedRoom) {
            $selectedRoom->load([
                'activeParticipations.user',
                'activeParticipations.studySubject',
                'participations' => fn ($query) => $query
                    ->with(['user', 'studySubject'])
                    ->where('status', StudyFocusParticipation::STATUS_COMPLETED)
                    ->latest('ended_at')
                    ->limit(5),
            ]);
        }

        $roomRankingPeriod = $request->query('room_ranking', 'today');
        $roomSummaries = $studyGroup->focusRooms
            ->mapWithKeys(fn (StudyFocusRoom $room) => [$room->id => $statistics->roomSummary($room)]);
        $roomRankings = $studyGroup->focusRooms
            ->mapWithKeys(fn (StudyFocusRoom $room) => [$room->id => $roomRanking->forRoom($room, $roomRankingPeriod)]);

        return view('study_groups.show', [
            'group' => $studyGroup,
            'membership' => $membership,
            'canManageRooms' => $membership?->canManageFocusRooms() ?? false,
            'summary' => $statistics->groupSummary($studyGroup),
            'ranking' => $ranking->forGroup($studyGroup, $request->query('ranking', 'today')),
            'selectedRoom' => $selectedRoom,
            'selectedRoomSummary' => $selectedRoom ? $statistics->roomSummary($selectedRoom) : null,
            'selectedRoomRanking' => $selectedRoom ? $roomRanking->forRoom($selectedRoom, $request->query('room_ranking', 'today')) : collect(),
            'roomSummaries' => $roomSummaries,
            'roomRankings' => $roomRankings,
            'subjects' => $request->user()->studySubjects()->orderBy('name')->get(),
            'activeParticipation' => $activeParticipation,
        ]);
    }

    public function update(UpdateStudyGroupRequest $request, StudyGroup $studyGroup)
    {
        Gate::authorize('update', $studyGroup);

        $data = $request->validated();

        $studyGroup->forceFill([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'visibility' => $data['visibility'],
            'password_hash' => $data['visibility'] === StudyGroup::VISIBILITY_PASSWORD
                ? Hash::make($data['password'])
                : null,
        ])->save();

        return redirect()
            ->route('study-groups.show', $studyGroup)
            ->with('status', 'Grupo atualizado.');
    }

    public function join(Request $request, StudyGroup $studyGroup, JoinStudyGroupAction $action)
    {
        Gate::authorize('join', $studyGroup);
        $this->ensurePasswordIfNeeded($request, $studyGroup);

        $alreadyMember = $this->membership($studyGroup, $request->user()) !== null;
        $action->execute($studyGroup, $request->user());

        return redirect()
            ->route('study-groups.show', $studyGroup)
            ->with('status', $alreadyMember ? 'Voce ja participa desse grupo.' : 'Voce entrou no grupo.');
    }

    public function joinByCode(Request $request, JoinStudyGroupAction $action)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12', Rule::exists('study_groups', 'code')],
            'password' => ['nullable', 'string', 'max:40'],
        ], [
            'code.exists' => 'Nenhum grupo encontrado com esse codigo.',
        ]);

        $group = StudyGroup::where('code', strtoupper(trim($data['code'])))->firstOrFail();
        Gate::authorize('join', $group);
        $this->ensurePasswordIfNeeded($request, $group);

        $action->execute($group, $request->user());

        return redirect()
            ->route('study-groups.show', $group)
            ->with('status', 'Voce entrou no grupo.');
    }

    public function leave(Request $request, StudyGroup $studyGroup)
    {
        Gate::authorize('leave', $studyGroup);

        $studyGroup->members()
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()
            ->route('study-groups.index')
            ->with('status', 'Voce saiu do grupo.');
    }

    public function storeFocusRoom(StoreFocusRoomRequest $request, StudyGroup $studyGroup, CreateFocusRoomAction $action)
    {
        Gate::authorize('manageFocusRooms', $studyGroup);

        if ($studyGroup->focusRooms()->where('name', $request->validated('name'))->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Esse grupo ja possui uma sala de foco com esse nome.',
            ]);
        }

        $room = $action->execute($studyGroup, $request->validated());

        return redirect()
            ->route('study-groups.show', ['studyGroup' => $studyGroup, 'room' => $room->id])
            ->with('status', 'Sala de foco criada.');
    }

    public function updateFocusRoom(StoreFocusRoomRequest $request, StudyGroup $studyGroup, StudyFocusRoom $focusRoom)
    {
        $this->ensureRoomBelongsToGroup($studyGroup, $focusRoom);
        Gate::authorize('update', $focusRoom);

        if ($studyGroup->focusRooms()->where('name', $request->validated('name'))->whereKeyNot($focusRoom->id)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Esse grupo ja possui uma sala de foco com esse nome.',
            ]);
        }

        $focusRoom->forceFill([
            'name' => $request->validated('name'),
            'description' => $request->validated('description') ?: null,
            'icon' => $request->validated('icon') ?: 'book',
        ])->save();

        return redirect()
            ->route('study-groups.show', ['studyGroup' => $studyGroup, 'room' => $focusRoom->id])
            ->with('status', 'Sala de foco atualizada.');
    }

    public function destroyFocusRoom(Request $request, StudyGroup $studyGroup, StudyFocusRoom $focusRoom)
    {
        $this->ensureRoomBelongsToGroup($studyGroup, $focusRoom);
        Gate::authorize('delete', $focusRoom);

        if ($focusRoom->participations()->exists() || $focusRoom->studySessions()->exists()) {
            $focusRoom->forceFill(['is_active' => false])->save();

            return redirect()
                ->route('study-groups.show', $studyGroup)
                ->with('status', 'Sala arquivada para preservar o historico.');
        }

        $focusRoom->delete();

        return redirect()
            ->route('study-groups.show', $studyGroup)
            ->with('status', 'Sala removida.');
    }

    public function showFocusRoom(Request $request, StudyGroup $studyGroup, StudyFocusRoom $focusRoom, StudyGroupStatisticsService $statistics, FocusRoomRankingService $ranking)
    {
        $this->ensureRoomBelongsToGroup($studyGroup, $focusRoom);
        Gate::authorize('view', $focusRoom);

        return redirect()->route('study-groups.show', [
            'studyGroup' => $studyGroup,
            'room' => $focusRoom->id,
        ]);
    }

    public function startFocusStudy(StartFocusStudyRequest $request, StudyGroup $studyGroup, StudyFocusRoom $focusRoom, StartFocusStudyAction $action)
    {
        $this->ensureRoomBelongsToGroup($studyGroup, $focusRoom);
        Gate::authorize('start', $focusRoom);

        $subject = StudySubject::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($request->validated('study_subject_id'))
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'study_subject_id' => 'Escolha uma materia cadastrada no seu perfil.',
            ]);
        }

        $action->execute($focusRoom, $request->user(), $subject, $request->validated('notes') ?? null);

        return redirect()
            ->route('study-groups.show', ['studyGroup' => $studyGroup, 'room' => $focusRoom->id])
            ->with('status', 'Estudo iniciado nessa sala.');
    }

    public function stopFocusStudy(Request $request, StudyGroup $studyGroup, StudyFocusRoom $focusRoom, StopFocusStudyAction $action)
    {
        $this->ensureRoomBelongsToGroup($studyGroup, $focusRoom);

        $participation = $request->user()->studyFocusParticipations()
            ->where('study_focus_room_id', $focusRoom->id)
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->first();

        abort_unless($participation, 403);

        $action->execute($participation);

        return redirect()
            ->route('study-groups.show', ['studyGroup' => $studyGroup, 'room' => $focusRoom->id])
            ->with('status', 'Estudo finalizado e salvo no historico.');
    }

    public function presence(StudyGroup $studyGroup, StudyGroupStatisticsService $statistics)
    {
        Gate::authorize('view', $studyGroup);

        $active = $statistics->activeParticipationsForGroup($studyGroup);

        return response()->json([
            'active_count' => $active->count(),
            'seconds_today' => $statistics->secondsTodayForGroup($studyGroup),
            'participants' => $active->map(fn (StudyFocusParticipation $participation) => [
                'name' => $participation->user->displayName(),
                'avatar' => mb_strtoupper(mb_substr($participation->user->displayName(), 0, 1)),
                'photo_url' => $participation->user->profilePhotoUrl(),
                'subject' => $participation->studySubject->name,
                'room' => $participation->focusRoom->name,
                'started_at' => $participation->started_at->toIso8601String(),
                'elapsed_seconds' => $participation->effectiveElapsedSeconds(),
                'is_paused' => $participation->isPaused(),
            ])->values(),
        ]);
    }

    private function membership(StudyGroup $group, $user): ?StudyGroupMember
    {
        return $group->members()
            ->where('user_id', $user->id)
            ->first();
    }

    private function searchGroups($user, string $search)
    {
        $normalized = mb_strtolower(ltrim($search, '#@'));

        return StudyGroup::query()
            ->with(['members:id,study_group_id,user_id'])
            ->withCount(['members', 'focusRooms'])
            ->where('status', StudyGroup::STATUS_ACTIVE)
            ->where(function ($query) use ($normalized) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$normalized.'%']);
            })
            ->where(function ($query) use ($user) {
                $query->whereIn('visibility', [StudyGroup::VISIBILITY_PUBLIC, StudyGroup::VISIBILITY_PASSWORD])
                    ->orWhere(function ($query) use ($user) {
                        $query->where('visibility', StudyGroup::VISIBILITY_FRIENDS)
                            ->whereHas('owner', fn ($query) => $query->whereKey($user->acceptedFriendIds()));
                    });
            })
            ->latest()
            ->limit(8)
            ->get();
    }

    private function ensurePasswordIfNeeded(Request $request, StudyGroup $group): void
    {
        if ($group->visibility !== StudyGroup::VISIBILITY_PASSWORD || $this->membership($group, $request->user())) {
            return;
        }

        if (! Hash::check((string) $request->input('password', ''), (string) $group->password_hash)) {
            throw ValidationException::withMessages([
                'password' => 'Informe a senha correta para entrar nesse grupo.',
            ]);
        }
    }

    private function ensureRoomBelongsToGroup(StudyGroup $group, StudyFocusRoom $focusRoom): void
    {
        abort_unless($focusRoom->study_group_id === $group->id, 404);
    }

    private function selectedRoom(StudyGroup $group, Request $request, ?StudyFocusParticipation $activeParticipation): ?StudyFocusRoom
    {
        $requestedRoomId = $request->integer('room');

        if ($requestedRoomId > 0) {
            $requestedRoom = $group->focusRooms->firstWhere('id', $requestedRoomId);

            if ($requestedRoom) {
                return $requestedRoom;
            }
        }

        if ($activeParticipation && $activeParticipation->focusRoom->study_group_id === $group->id) {
            return $group->focusRooms->firstWhere('id', $activeParticipation->study_focus_room_id)
                ?: $activeParticipation->focusRoom;
        }

        return $group->focusRooms->first();
    }
}
