@extends('layouts.app')

@php
    $selectedRoomId = $selectedRoom?->id;
@endphp

@section('content')
    <div class="container study-group-page">
        <section class="hero subjects-hero study-groups-hero" data-study-group-presence data-presence-url="{{ route('study-groups.presence', $group) }}">
            <div>
                <p class="eyebrow">Grupo {{ $group->code }}</p>
                <h1>{{ $group->name }}</h1>
                <p class="lead">{{ $group->description ?: 'Uma comunidade de estudos focada em presenca e constancia.' }}</p>
                <div class="study-group-hero-stats">
                    <span>{{ $summary['members_count'] }} membros</span>
                    <span data-presence-active-count>{{ $summary['active_count'] }} estudando agora</span>
                    <span data-presence-seconds-today>{{ gmdate('H:i:s', $summary['seconds_today']) }} hoje</span>
                    <span>{{ $group->visibilityLabel() }}</span>
                </div>
            </div>

            <div class="subjects-hero-actions">
                <a href="{{ route('study-groups.index') }}" class="secondary-button">Meus grupos</a>
                @if (! $membership)
                    <form action="{{ route('study-groups.join', $group) }}" method="POST">
                        @csrf
                        <button type="submit" class="primary-button">Participar</button>
                    </form>
                @elseif ($membership->role !== \App\Models\StudyGroupMember::ROLE_OWNER)
                    <form action="{{ route('study-groups.leave', $group) }}" method="POST">
                        @csrf
                        <button type="submit" class="ghost-button">Sair do grupo</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="study-group-layout study-group-server-layout" data-focus-room-workspace>
            <main class="study-group-main">
                <article class="panel focus-room-board">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Salas de foco</p>
                            <h2>Escolha onde estudar</h2>
                        </div>
                    </div>

                    <div class="focus-room-grid focus-room-channel-list" role="list">
                        @forelse ($group->focusRooms as $room)
                            @php
                                $roomSummary = $roomSummaries[$room->id] ?? ['active_count' => $room->activeParticipations->count(), 'seconds_today' => 0];
                                $isStudyingRoom = $activeParticipation && $activeParticipation->study_focus_room_id === $room->id;
                                $isExpandedRoom = $selectedRoomId === $room->id;
                            @endphp

                            <article class="focus-room-card focus-room-channel {{ ! $room->is_active ? 'is-muted' : '' }} {{ $isExpandedRoom ? 'is-expanded' : '' }}" data-focus-room-card data-focus-room-id="{{ $room->id }}" role="listitem">
                                <a href="{{ route('study-groups.show', ['studyGroup' => $group, 'room' => $room->id]) }}" class="focus-room-channel-link" data-focus-room-select data-focus-room-id="{{ $room->id }}">
                                    <span class="focus-room-icon">
                                        @include('partials.dashboard.icon', ['name' => $room->icon ?: 'book', 'class' => 'icon-svg'])
                                    </span>
                                    <span>
                                        <strong>{{ $room->name }}</strong>
                                        <small>{{ $room->description ?: 'Sala para estudos em foco.' }}</small>
                                    </span>
                                    <span class="status-pill {{ $room->activeParticipations->isNotEmpty() ? 'status-pill-live' : '' }}">
                                        {{ $room->activeParticipations->count() }} agora
                                    </span>
                                    <span class="focus-room-avatars">
                                        @foreach ($room->activeParticipations->take(5) as $participation)
                                            @include('partials.user-avatar', ['user' => $participation->user, 'class' => 'focus-room-avatar'])
                                        @endforeach
                                    </span>
                                </a>

                                <div class="focus-room-call" data-focus-room-details @if (! $isExpandedRoom) hidden @endif>
                                    <div class="focus-room-call-summary">
                                        <div>
                                            <p class="eyebrow">Canal ativo</p>
                                            <h3>{{ $room->name }}</h3>
                                        </div>
                                        <div class="focus-room-call-pills">
                                            <span>{{ $roomSummary['active_count'] }} agora</span>
                                            <span>{{ gmdate('H:i:s', $roomSummary['seconds_today']) }} hoje</span>
                                        </div>
                                    </div>

                                    <section class="focus-room-call-details">
                                        <div>
                                            <p class="eyebrow">Estudando agora</p>
                                            <div class="focus-room-call-people">
                                                @forelse ($room->activeParticipations as $participation)
                                                    <article class="focus-room-person">
                                                        @include('partials.user-avatar', ['user' => $participation->user])
                                                        <div>
                                                            <strong>{{ $participation->user->displayName() }}</strong>
                                                            <p>{{ $participation->studySubject->name }}</p>
                                                            <small data-study-timer data-started-at="{{ $participation->started_at->toIso8601String() }}" data-rendered-at="{{ now()->toIso8601String() }}" data-base-seconds="0" data-elapsed-seconds="{{ $participation->effectiveElapsedSeconds() }}" data-state="{{ $participation->isPaused() ? 'paused' : 'running' }}">
                                                                {{ $participation->isPaused() ? 'Pausado em' : 'Estudando ha' }} <span data-timer-value>{{ gmdate('H:i:s', $participation->effectiveElapsedSeconds()) }}</span>
                                                            </small>
                                                        </div>
                                                    </article>
                                                @empty
                                                    <div class="empty-state compact-empty">
                                                        <strong>Ninguem estudando aqui agora.</strong>
                                                        <p>Escolha uma materia e de play para aparecer nesta sala.</p>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>

                                        <div class="focus-room-call-controls">
                                            <p class="eyebrow">Seu foco</p>

                                            @if ($isStudyingRoom)
                                                <div class="timer-widget" data-study-timer data-started-at="{{ $activeParticipation->started_at->toIso8601String() }}" data-rendered-at="{{ now()->toIso8601String() }}" data-base-seconds="0" data-elapsed-seconds="{{ $activeParticipation->effectiveElapsedSeconds() }}" data-state="{{ $activeParticipation->isPaused() ? 'paused' : 'running' }}">
                                                    <div class="timer-subject">
                                                        <span class="timer-dot"></span>
                                                        <strong>{{ $activeParticipation->studySubject->name }}</strong>
                                                    </div>
                                                    <div class="timer-value" data-timer-value>{{ gmdate('H:i:s', $activeParticipation->effectiveElapsedSeconds()) }}</div>
                                                </div>

                                                <div class="timer-actions stacked-actions">
                                                    @if ($activeParticipation->isPaused())
                                                        <form action="{{ route('study-sessions.resume', $activeParticipation->study_session_id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="secondary-button full-width">Retomar</button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('study-sessions.pause', $activeParticipation->study_session_id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="secondary-button full-width">Pausar</button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('study-groups.focus-rooms.stop', [$group, $room]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="primary-button full-width">Finalizar estudo</button>
                                                    </form>
                                                </div>
                                            @elseif ($activeParticipation)
                                                <div class="empty-state compact-empty">
                                                    <strong>Voce ja esta estudando em outra sala.</strong>
                                                    <p>Finalize {{ $activeParticipation->focusRoom->name }} antes de iniciar outro foco.</p>
                                                    <a href="{{ route('study-groups.show', ['studyGroup' => $activeParticipation->focusRoom->group, 'room' => $activeParticipation->focusRoom->id]) }}" class="secondary-button">Abrir sala ativa</a>
                                                </div>
                                            @elseif (! $room->is_active)
                                                <p class="friend-search-state">Essa sala esta arquivada.</p>
                                            @elseif ($subjects->isEmpty())
                                                <div class="empty-state compact-empty">
                                                    <strong>Crie uma materia primeiro.</strong>
                                                    <p>As salas usam suas materias pessoais para manter metas e historico corretos.</p>
                                                    <a href="{{ route('study-subjects.index') }}#criar" class="primary-button full-width">Criar materia</a>
                                                </div>
                                            @else
                                                <form action="{{ route('study-groups.focus-rooms.start', [$group, $room]) }}" method="POST" class="study-room-form">
                                                    @csrf
                                                    <label class="subject-combobox" data-subject-combobox>
                                                        <span>Materia pessoal</span>
                                                        <input type="text" name="study_subject_name" placeholder="Digite para buscar..." autocomplete="off" data-subject-search required>
                                                        <input type="hidden" name="study_subject_id" data-subject-id>
                                                        <div class="subject-options" data-subject-options>
                                                            @foreach ($subjects as $subject)
                                                                <button type="button" class="subject-option" data-subject-option data-subject-id="{{ $subject->id }}" data-subject-name="{{ $subject->name }}">
                                                                    {{ $subject->name }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </label>
                                                    <button type="submit" class="primary-button full-width">Play / Iniciar estudo</button>
                                                </form>
                                            @endif
                                        </div>
                                    </section>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state">
                                <strong>Nenhuma sala de foco ainda.</strong>
                                <p>Administradores podem criar salas como Matematica, Redacao ou Ciencias da Natureza.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                @if ($canManageRooms)
                    <details class="panel focus-admin-details">
                        <summary class="focus-admin-summary">
                            <div>
                                <p class="eyebrow">Administracao</p>
                                <h2>Criar sala de foco</h2>
                            </div>
                            <span class="secondary-button">Nova sala</span>
                        </summary>

                        <form action="{{ route('study-groups.focus-rooms.store', $group) }}" method="POST" class="study-room-form compact-form">
                            @csrf
                            <label>
                                <span>Nome</span>
                                <input type="text" name="name" maxlength="120" placeholder="Ex: Ciencias da Natureza" required>
                            </label>
                            <label>
                                <span>Descricao</span>
                                <input type="text" name="description" maxlength="300" placeholder="Tema amplo desta sala">
                            </label>
                            <label>
                                <span>Icone</span>
                                <select name="icon">
                                    <option value="book">Livro</option>
                                    <option value="target">Alvo</option>
                                    <option value="notes">Notas</option>
                                    <option value="clock">Tempo</option>
                                </select>
                            </label>
                            <button type="submit" class="primary-button">Criar sala</button>
                        </form>
                    </details>
                @endif
            </main>
        </section>
    </div>
@endsection
