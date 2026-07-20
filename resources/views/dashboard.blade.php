@extends('layouts.app')

@section('content')
    <div class="dashboard-shell" id="inicio">
        <aside class="dashboard-sidebar">
            <section class="sidebar-panel brand-panel">
                <a class="brand brand-large" href="{{ route('dashboard') }}">
                    <span class="brand-mark brand-mark-lg">M</span>
                    <span>
                        <strong>MogStudy</strong>
                        <small>GitHub de estudos</small>
                    </span>
                </a>
            </section>

            <a class="sidebar-panel profile-card profile-card-link" href="{{ route('profile.show', $user) }}" aria-label="Abrir seu perfil">
                @if ($profile['photo_url'])
                    <img class="profile-avatar profile-avatar-image" src="{{ $profile['photo_url'] }}" alt="Foto de {{ $profile['title'] }}">
                @else
                    <div class="profile-avatar">{{ $profile['avatar'] }}</div>
                @endif
                <div class="profile-body">
                    <h2>{{ $profile['title'] }}</h2>
                    <p>{{ '@'.$profile['username'] }}</p>
                    <span>{{ $profile['bio'] }}</span>
                </div>

                <div class="profile-level">
                    <div class="profile-level-head">
                        <strong>Nivel {{ $profile['level'] }}</strong>
                        <span>{{ $profile['xp_current'] }}/{{ $profile['xp_goal'] }} XP</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill progress-fill-violet" style="width: {{ $profile['xp_percent'] }}%"></div>
                    </div>
                </div>
            </a>

            <nav class="sidebar-panel sidebar-nav" aria-label="Navegacao principal">
                @foreach ($sidebarItems as $item)
                    <a href="{{ $item['href'] }}" class="sidebar-nav-item {{ $item['active'] ? 'is-active' : '' }}">
                        <span class="sidebar-icon">
                            @include('partials.dashboard.icon', ['name' => $item['icon'], 'class' => 'icon-svg'])
                        </span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <section
                class="sidebar-panel sidebar-cta"
                data-friend-search
                data-friend-search-url="{{ route('friend-search') }}"
                data-friend-search-empty="Dica: compartilhe seu {{ '@'.$profile['username'] }} com amigos."
            >
                <div>
                    <p class="eyebrow">Encontrar amigos</p>
                    <h3>Busque pelo @username da pessoa.</h3>
                    <p>Use o nome publico ou o identificador unico para adicionar alguem ao seu ciclo.</p>
                </div>

                <form action="{{ route('dashboard') }}" method="GET" class="friend-search-form" data-friend-search-form>
                    <label>
                        <span class="visually-hidden">Buscar amigos</span>
                        <span class="friend-search-input">
                            @include('partials.dashboard.icon', ['name' => 'search', 'class' => 'icon-svg'])
                            <input
                                type="search"
                                name="friend_search"
                                value="{{ $friendSearch['query'] }}"
                                placeholder="Buscar @username ou nome"
                                autocomplete="off"
                                data-friend-search-input
                            >
                        </span>
                    </label>
                    <button type="submit" class="secondary-button full-width" data-friend-search-submit>Buscar</button>
                </form>

                <div class="friend-search-results" data-friend-search-results aria-live="polite">
                    @if ($friendSearch['has_search'])
                        @forelse ($friendSearch['results'] as $result)
                            @php($candidate = $result['user'])
                            @php($friendship = $result['friendship'])
                            <article class="friend-search-result">
                                <a href="{{ route('profile.show', $candidate) }}" class="friend-search-person">
                                    @if ($result['photo_url'])
                                        <img class="friend-avatar friend-avatar-image" src="{{ $result['photo_url'] }}" alt="Foto de {{ $candidate->displayName() }}">
                                    @else
                                        <span class="friend-avatar">{{ $result['avatar'] }}</span>
                                    @endif
                                    <span class="friend-search-copy">
                                        <strong>{{ $candidate->displayName() }}</strong>
                                        <small>{{ '@'.$candidate->username }}</small>
                                    </span>
                                </a>

                                <div class="friend-search-action">
                                    @if ($friendship['state'] === 'none')
                                        <form action="{{ route('friendships.store', $candidate) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="mini-button">Adicionar</button>
                                        </form>
                                    @elseif ($friendship['state'] === 'sent')
                                        <span class="status-pill">Pedido enviado</span>
                                        <form action="{{ route('friendships.destroy', $friendship['friendship']) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ghost-button">Cancelar</button>
                                        </form>
                                    @elseif ($friendship['state'] === 'received')
                                        <form action="{{ route('friendships.accept', $friendship['friendship']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="mini-button">Aceitar</button>
                                        </form>
                                    @else
                                        <span class="status-pill status-pill-live">Amigos</span>
                                        <form action="{{ route('friendships.destroy', $friendship['friendship']) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ghost-button">Remover</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="friend-search-state">Nenhuma pessoa encontrada com essa busca.</p>
                        @endforelse
                    @else
                        <p class="friend-search-hint">Dica: compartilhe seu {{ '@'.$profile['username'] }} com amigos.</p>
                    @endif
                </div>

                <form action="{{ route('logout') }}" method="POST" class="sidebar-logout">
                    @csrf
                    <button type="submit" class="ghost-button full-width">Sair</button>
                </form>
            </section>
        </aside>

        <main class="dashboard-main">
            <section class="dashboard-panel hero-panel-main hero-panel-dashboard">
                <div class="hero-copy">
                    <p class="eyebrow">{{ $greeting }}</p>
                    <h1>{{ $greeting }}, {{ $profile['display_name'] }}!</h1>
                    <p class="lead">{{ $heroSubtitle }}</p>

                    <div class="hero-chips">
                        <span class="hero-chip">{{ $streak }} dias</span>
                        <span class="hero-chip">{{ $totals['today_label'] }} hoje</span>
                    </div>
                </div>

                <details class="friend-bell hero-friend-bell">
                        <summary aria-label="Notificacoes de amizade">
                            @include('partials.dashboard.icon', ['name' => 'bell', 'class' => 'icon-svg'])
                            @if ($friendNotifications['count'] > 0)
                                <span>{{ $friendNotifications['count'] }}</span>
                            @endif
                        </summary>

                        <div class="friend-bell-menu">
                            <p class="eyebrow">Amizades</p>

                            @forelse ($friendNotifications['pending_received'] as $friendship)
                                <article class="friend-notification">
                                    <div>
                                        <strong>{{ $friendship->requester->displayName() }}</strong>
                                        <small>enviou um pedido de amizade</small>
                                    </div>
                                    <form action="{{ route('friendships.accept', $friendship) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="mini-button">Aceitar</button>
                                    </form>
                                </article>
                            @empty
                                <p class="muted">Nenhum pedido pendente.</p>
                            @endforelse

                            @foreach ($friendNotifications['accepted_sent'] as $friendship)
                                <article class="friend-notification friend-notification-muted">
                                    <div>
                                        <strong>{{ $friendship->addressee->displayName() }}</strong>
                                        <small>aceitou seu pedido de amizade</small>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                </details>

                <div class="hero-status">
                    <div class="streak-badge">
                        <span class="streak-icon">S</span>
                        <div>
                            <strong>{{ $streak }} dias</strong>
                            <small>sequencia atual</small>
                        </div>
                    </div>
                </div>
            </section>

            <section class="dashboard-panel heatmap-panel" id="ranking">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Sua atividade</p>
                        <h2>Contribuicoes no ultimo ano</h2>
                    </div>

                    <button type="button" class="mini-button">Ultimos 365 dias</button>
                </div>

                @include('partials.dashboard.heatmap', ['heatmap' => $heatmap])
            </section>

            <section class="metrics-grid">
                @foreach ($metrics as $metric)
                    <article class="dashboard-panel metric-card metric-card-{{ $metric['tone'] }}">
                        <div class="metric-icon">
                            @include('partials.dashboard.icon', ['name' => $metric['icon'], 'class' => 'icon-svg'])
                        </div>
                        <div class="metric-copy">
                            <strong>{{ $metric['value'] }}</strong>
                            <span>{{ $metric['label'] }}</span>
                            <small>{{ $metric['subtext'] }}</small>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="dashboard-split">
                <article class="dashboard-panel activity-panel" id="anotacoes">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Atividade recente</p>
                            <h2>Seu diario e feed</h2>
                        </div>

                        <a href="{{ route('profile.show', $user) }}" class="mini-link">Ver perfil</a>
                    </div>

                    <form action="{{ route('daily-logs.store') }}" method="POST" class="quick-note-form">
                        @csrf

                        <div class="quick-note-row">
                            <label>
                                <span>Data</span>
                                <input
                                    type="date"
                                    name="log_date"
                                    value="{{ old('log_date', optional($todayLog?->log_date)->format('Y-m-d') ?? now()->toDateString()) }}"
                                >
                            </label>

                            <label>
                                <span>Titulo</span>
                                <input type="text" name="title" value="{{ old('title', $todayLog?->title) }}" placeholder="O que marcou seu dia?" required>
                            </label>
                        </div>

                        <label>
                            <span>Conteudo</span>
                            <textarea name="content" rows="4" placeholder="Escreva o que aprendeu hoje..." required>{{ old('content', $todayLog?->content) }}</textarea>
                        </label>

                        <button type="submit" class="primary-button">Salvar anotacao</button>
                    </form>

                    <div class="feed-list">
                        @forelse ($recentActivity as $item)
                            <article class="feed-item">
                                <span class="feed-avatar feed-avatar-{{ $item['accent'] }}">{{ $item['avatar'] }}</span>
                                <div class="feed-content">
                                    <div class="feed-meta">
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['when'] }}</span>
                                    </div>
                                    <p>{{ $item['detail'] }}</p>
                                </div>
                            </article>
                        @empty
                            <p class="muted">Nenhuma atividade ainda.</p>
                        @endforelse
                    </div>
                </article>

                <article class="dashboard-panel subjects-panel" id="materias">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Materias</p>
                            <h2>Foco atual</h2>
                        </div>

                        <a href="{{ route('study-subjects.index') }}" class="mini-link">Ver todas</a>
                    </div>

                    <div class="subject-summary-actions">
                        <span>{{ $studySubjects->count() }} cadastrada{{ $studySubjects->count() === 1 ? '' : 's' }}</span>
                        <a href="{{ route('study-subjects.index') }}#criar" class="secondary-button">Criar materia</a>
                    </div>

                    <div class="subject-list">
                        @forelse ($subjects as $subject)
                            <article class="subject-row">
                                <div class="subject-row-main">
                                    <div class="subject-row-head">
                                        @if ($subject['photo_url'])
                                            <img class="subject-photo" src="{{ $subject['photo_url'] }}" alt="Foto de {{ $subject['name'] }}">
                                        @else
                                            <span class="subject-icon subject-icon-{{ $subject['tone'] }}">
                                                @include('partials.dashboard.icon', ['name' => $subject['icon'], 'class' => 'icon-svg'])
                                            </span>
                                        @endif

                                        <div class="subject-copy">
                                            <strong>{{ $subject['name'] }}</strong>
                                            <p>{{ $subject['hours_label'] }}</p>
                                            <small>{{ $subject['goal_label'] }}</small>
                                        </div>
                                    </div>

                                    <div class="subject-progress">
                                        <span>Progresso {{ $subject['goal_progress'] }}%</span>
                                        <div class="progress-track progress-track-small">
                                            <div class="progress-fill progress-fill-violet" style="width: {{ $subject['goal_progress'] }}%"></div>
                                        </div>
                                    </div>

                                    <a href="{{ route('study-subjects.index') }}#materia-{{ $subject['id'] }}" class="icon-button icon-button-small" aria-label="Editar {{ $subject['name'] }}">
                                        @include('partials.dashboard.icon', ['name' => 'pencil', 'class' => 'icon-svg'])
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state">
                                <strong>Nenhuma materia cadastrada ainda.</strong>
                                <p>Abra a tela de materias para criar a primeira e liberar o timer.</p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="dashboard-panel circle-panel" id="amigos">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Ciclo social</p>
                        <h2>Ciclo de estudos</h2>
                        <p class="section-subtitle">Novidades, posts e sessoes recentes do seu ciclo.</p>
                    </div>

                    <details class="circle-compose">
                        <summary class="circle-compose-button" aria-label="Criar post no ciclo">
                            <span>+</span>
                            <strong>Novo post</strong>
                        </summary>
                        <form action="{{ route('circle-posts.store') }}" method="POST" class="circle-compose-form">
                            @csrf
                            <label>
                                <span>Titulo</span>
                                <input type="text" name="title" value="{{ old('title') }}" maxlength="80" placeholder="Uma conquista, duvida ou foco..." required>
                            </label>
                            <label>
                                <span>Texto</span>
                                <textarea name="body" rows="3" maxlength="200" placeholder="Compartilhe com seu ciclo em ate 200 caracteres." required>{{ old('body') }}</textarea>
                            </label>
                            <button type="submit" class="primary-button full-width">Publicar</button>
                        </form>
                    </details>
                </div>

                <div class="circle-layout">
                    <div class="circle-feed circle-timeline">
                        @forelse ($circle['feed'] as $item)
                            @if ($item['type'] === 'post')
                                @php($post = $item['post'])
                                @php($postInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->user->displayName(), 0, 1)))
                                <article class="circle-post circle-timeline-item">
                                    <div class="circle-timeline-marker">
                                        <span class="circle-avatar">{{ $postInitial }}</span>
                                        <span class="circle-event-icon circle-event-icon-post">
                                            @include('partials.dashboard.icon', ['name' => 'notes', 'class' => 'icon-svg'])
                                        </span>
                                    </div>

                                    <div class="circle-card-content">
                                        <div class="circle-card-head">
                                            <div>
                                                <span class="circle-kicker">Publicacao</span>
                                                <h3>{{ $post->user->displayName() }} publicou: {{ $post->title }}</h3>
                                            </div>
                                            <time>{{ $post->created_at->diffForHumans() }}</time>
                                        </div>

                                        <p class="circle-body">{{ $post->body }}</p>

                                        @if ($post->replies->isNotEmpty())
                                            <div class="circle-replies">
                                                <span class="circle-thread-label">Respostas recentes</span>
                                                @foreach ($post->replies->take(3) as $reply)
                                                    <div class="circle-reply">
                                                        <strong>{{ $reply->user->displayName() }}</strong>
                                                        <span>{{ $reply->body }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <form action="{{ route('circle-posts.replies.store', $post) }}" method="POST" class="circle-reply-form">
                                            @csrf
                                            <input type="text" name="body" maxlength="200" placeholder="Responder em ate 200 caracteres..." required>
                                            <button type="submit" class="mini-button">Responder</button>
                                        </form>
                                    </div>
                                </article>
                            @else
                                @php($session = $item['session'])
                                @php($sessionInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($session->user->displayName(), 0, 1)))
                                <article class="circle-post circle-session circle-timeline-item">
                                    <div class="circle-timeline-marker">
                                        <span class="circle-avatar">{{ $sessionInitial }}</span>
                                        <span class="circle-event-icon circle-event-icon-session">
                                            @include('partials.dashboard.icon', ['name' => 'clock', 'class' => 'icon-svg'])
                                        </span>
                                    </div>

                                    <div class="circle-card-content">
                                        <div class="circle-card-head">
                                            <div>
                                                <span class="circle-kicker">Sessao de estudo</span>
                                                <h3>{{ $session->user->displayName() }} comecou a estudar {{ $session->subject }}</h3>
                                            </div>
                                            <time>{{ $session->started_at->diffForHumans() }}</time>
                                        </div>
                                        <p class="circle-body">{{ $session->ended_at ? 'Sessao finalizada: '.$session->duration_label : 'Sessao em andamento agora.' }}</p>
                                    </div>
                                </article>
                            @endif
                        @empty
                            <div class="empty-state circle-empty-state">
                                <strong>Seu ciclo ainda esta quieto.</strong>
                                <p>Publique uma novidade no botao acima ou adicione amigos pelos perfis para montar sua timeline de estudos.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>

        <aside class="dashboard-rail">
            <section class="dashboard-panel timer-panel" id="sessoes">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Cronometro</p>
                        <h2>Sessao atual</h2>
                    </div>

                    @if ($currentSession)
                        <span class="status-pill {{ $currentSession->isPaused() ? '' : 'status-pill-live' }}">{{ $currentSession->isPaused() ? 'Pausado' : 'Ao vivo' }}</span>
                    @else
                        <span class="status-pill">Idle</span>
                    @endif
                </div>

                <div
                    class="timer-widget"
                    data-study-timer
                    data-started-at="{{ $timer['started_at'] }}"
                    data-rendered-at="{{ $timer['rendered_at'] }}"
                    data-base-seconds="{{ $timer['base_seconds'] }}"
                    data-elapsed-seconds="{{ $timer['elapsed_seconds'] }}"
                    data-state="{{ $timer['state'] }}"
                >
                    <div class="timer-subject">
                        <span class="timer-dot"></span>
                        <strong>{{ $timer['subject'] }}</strong>
                    </div>

                    <div class="timer-value" data-timer-value>{{ $timer['display'] }}</div>

                    @if ($currentSession)
                        <p class="muted">Iniciada em {{ $currentSession->started_at->format('d/m/Y H:i') }}</p>
                        <div class="timer-actions">
                            @if ($currentSession->isPaused())
                                <form action="{{ route('study-sessions.resume', $currentSession) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="secondary-button">Retomar</button>
                                </form>
                            @else
                                <form action="{{ route('study-sessions.pause', $currentSession) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="secondary-button">Pausar</button>
                                </form>
                            @endif

                            <form action="{{ route('study-sessions.stop', $currentSession) }}" method="POST">
                                @csrf
                                <button type="submit" class="primary-button">Finalizar sessao</button>
                            </form>
                        </div>
                    @else
                        <p class="muted">Nenhuma sessao em andamento agora.</p>
                        @if ($studySubjects->isNotEmpty())
                            <form action="{{ route('study-sessions.store') }}" method="POST" class="quick-start-form">
                                @csrf

                                <label class="subject-combobox" data-subject-combobox>
                                    <span>Materia</span>
                                    <input
                                        type="text"
                                        name="study_subject_name"
                                        value="{{ old('study_subject_name') }}"
                                        placeholder="Digite para buscar..."
                                        autocomplete="off"
                                        required
                                        data-subject-search
                                    >
                                    <input type="hidden" name="study_subject_id" value="{{ old('study_subject_id') }}" data-subject-id>
                                    <div class="subject-options" data-subject-options>
                                        @foreach ($studySubjects as $subject)
                                            <button
                                                type="button"
                                                class="subject-option"
                                                data-subject-option
                                                data-subject-id="{{ $subject->id }}"
                                                data-subject-name="{{ $subject->name }}"
                                            >
                                                {{ $subject->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </label>

                                <label>
                                    <span>Notas rapidas</span>
                                    <textarea name="notes" rows="3" placeholder="Objetivo da sessao">{{ old('notes') }}</textarea>
                                </label>

                                <button type="submit" class="primary-button full-width">Comecar agora</button>
                            </form>
                        @else
                            <div class="empty-state">
                                <strong>Crie uma materia primeiro.</strong>
                                <p>Depois ela aparece aqui para iniciar o timer com autocomplete.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </section>

            <section class="dashboard-panel group-session-panel">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Grupos de estudo</p>
                        <h2>Estude junto</h2>
                    </div>
                    <a href="{{ route('study-groups.index') }}" class="mini-link">Ver todos</a>
                </div>

                @if ($studyGroups['active_participation'])
                    @php($activeGroupStudy = $studyGroups['active_participation'])
                    <article class="group-active-mini">
                        <span class="status-pill status-pill-live">Ao vivo</span>
                        <strong>Voce esta estudando {{ $activeGroupStudy->studySubject->name }}</strong>
                        <p>{{ $activeGroupStudy->focusRoom->name }} · {{ $activeGroupStudy->focusRoom->group->name }}</p>
                        <a href="{{ route('study-groups.show', ['studyGroup' => $activeGroupStudy->focusRoom->group, 'room' => $activeGroupStudy->focusRoom->id]) }}" class="secondary-button full-width">Abrir sala</a>
                    </article>
                @elseif ($studyGroups['groups']->isNotEmpty())
                    <div class="dashboard-group-list">
                        @foreach ($studyGroups['groups'] as $groupSummary)
                            @php($group = $groupSummary['model'])
                            <a href="{{ route('study-groups.show', $group) }}" class="dashboard-group-item">
                                <span class="study-group-mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($group->name, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $group->name }}</strong>
                                    <small>{{ $groupSummary['active_count'] }} estudando agora · {{ gmdate('H:i:s', $groupSummary['seconds_today']) }} hoje</small>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <strong>Voce ainda nao participa de nenhum grupo.</strong>
                        <p>Entre em uma comunidade com pessoas que tem o mesmo objetivo.</p>
                    </div>
                @endif

                <div class="group-session-actions">
                    <a href="{{ route('study-groups.index') }}" class="primary-button full-width">Explorar grupos</a>
                    <a href="{{ route('study-groups.create') }}" class="secondary-button full-width">Criar grupo</a>
                </div>
            </section>

            <section class="dashboard-panel goal-panel" id="metas">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">{{ $goal['title'] }}</p>
                        <h2>Foco de hoje</h2>
                    </div>

                    <a href="{{ route('study-subjects.index') }}" class="mini-link">Editar metas</a>
                </div>

                @if ($goal['has_goal'])
                    <div class="goal-grid">
                        <div class="goal-ring" style="--progress: {{ $goal['progress'] }}%">
                            <div class="goal-ring-inner">
                                <strong>{{ $goal['done_label'] }}</strong>
                                <span>de {{ $goal['target_label'] }}</span>
                            </div>
                        </div>

                        <div class="goal-copy">
                            <strong>Faltam {{ $goal['remaining_label'] }}</strong>
                            <p>Soma das metas semanais de {{ $goal['subjects_count'] }} materia{{ $goal['subjects_count'] === 1 ? '' : 's' }}.</p>
                            <div class="goal-bars" aria-hidden="true">
                                @foreach ($goal['bars'] as $bar)
                                    <span style="height: {{ $bar * 2 }}px"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <strong>Nenhuma meta semanal definida.</strong>
                        <p>Cadastre uma meta nas materias para acompanhar o foco da semana.</p>
                        <a href="{{ route('study-subjects.index') }}" class="secondary-button">Editar materias</a>
                    </div>
                @endif
            </section>

            <section class="dashboard-panel achievements-panel" id="conquistas">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Conquistas</p>
                        <h2>Recentes</h2>
                    </div>

                    <a href="#" class="mini-link">Ver todas</a>
                </div>

                <div class="achievement-list">
                    @foreach ($achievements as $achievement)
                        <article class="achievement-item">
                            <span class="achievement-icon achievement-icon-{{ $achievement['tone'] }}">
                                @include('partials.dashboard.icon', ['name' => $achievement['icon'], 'class' => 'icon-svg'])
                            </span>
                            <div class="achievement-copy">
                                <strong>{{ $achievement['title'] }}</strong>
                                <p>{{ $achievement['detail'] }}</p>
                            </div>
                            <span class="achievement-time">{{ $achievement['when'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

        </aside>
    </div>
@endsection
