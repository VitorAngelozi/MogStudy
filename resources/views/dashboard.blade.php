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

            <section class="sidebar-panel profile-card">
                <div class="profile-avatar">{{ $profile['avatar'] }}</div>
                <div class="profile-body">
                    <h2>{{ $profile['display_name'] }}</h2>
                    <p>@{{ $profile['username'] }}</p>
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
            </section>

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

            <section class="sidebar-panel sidebar-cta">
                <div>
                    <p class="eyebrow">Convide amigos</p>
                    <h3>Estude junto e mantenha a sequencia viva.</h3>
                    <p>Espaco reservado para colaboracao social no futuro.</p>
                </div>

                <button type="button" class="secondary-button full-width">Convidar</button>

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
                        <span class="hero-chip">{{ $profile['readme_words'] }} palavras no README</span>
                    </div>
                </div>

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

                                    <div class="subject-progress">
                                        <span>{{ $subject['goal_progress'] }}%</span>
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

            <section class="dashboard-panel projects-panel" id="projetos">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Projetos e README</p>
                        <h2>Edite seu perfil e acompanhe iniciativas</h2>
                    </div>

                    <a href="{{ route('profile.show', $user) }}" class="mini-link">Abrir perfil</a>
                </div>

                <div class="projects-grid">
                    <article class="mini-panel">
                        <div class="section-heading section-heading-tight">
                            <div>
                                <p class="eyebrow">README</p>
                                <h3>Markdown do perfil</h3>
                            </div>
                        </div>

                        <form action="{{ route('readme.update') }}" method="POST" class="quick-note-form">
                            @csrf
                            @method('PUT')

                            <label>
                                <span>README em markdown</span>
                                <textarea name="readme_markdown" rows="8" maxlength="500" placeholder="# Sobre mim" data-character-counter="readme-counter">{{ old('readme_markdown', $user->readme_markdown ?: $user->defaultReadmeTemplate()) }}</textarea>
                                <small class="muted character-counter" id="readme-counter">0/500 caracteres</small>
                            </label>

                            <button type="submit" class="secondary-button">Atualizar README</button>
                        </form>
                    </article>

                    <article class="mini-panel">
                        <div class="section-heading section-heading-tight">
                            <div>
                                <p class="eyebrow">Projetos</p>
                                <h3>Em andamento</h3>
                            </div>
                        </div>

                        <div class="project-list">
                            <div class="project-card">
                                <strong>MogStudy redesign</strong>
                                <p>Dashboard escuro com foco em consistencia e aprendizado visual.</p>
                            </div>
                            <div class="project-card">
                                <strong>Timer de foco</strong>
                                <p>Widget de sessao com inicio, pausa visual e finalizacao.</p>
                            </div>
                            <div class="project-card">
                                <strong>Feed diario</strong>
                                <p>Registros do dia organizados no estilo de contribuicoes.</p>
                            </div>
                        </div>
                    </article>
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
                        <span class="status-pill status-pill-live">Ao vivo</span>
                    @else
                        <span class="status-pill">Idle</span>
                    @endif
                </div>

                <div
                    class="timer-widget"
                    data-study-timer
                    data-started-at="{{ $timer['started_at'] }}"
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
                            <button type="button" class="secondary-button" data-timer-pause>Pausar</button>

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

            <section class="dashboard-panel goal-panel" id="metas">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Meta diaria</p>
                        <h2>Foco de hoje</h2>
                    </div>

                    <a href="#anotacoes" class="mini-link">Editar meta</a>
                </div>

                <div class="goal-grid">
                    <div class="goal-ring" style="--progress: {{ $goal['progress'] }}%">
                        <div class="goal-ring-inner">
                            <strong>{{ $goal['done_label'] }}</strong>
                            <span>de {{ $goal['target_label'] }}</span>
                        </div>
                    </div>

                    <div class="goal-copy">
                        <strong>Faltam {{ $goal['remaining_label'] }}</strong>
                        <p>Mantenha o foco e complete sua meta de hoje.</p>
                        <div class="goal-bars" aria-hidden="true">
                            @foreach ($goal['bars'] as $bar)
                                <span style="height: {{ $bar * 2 }}px"></span>
                            @endforeach
                        </div>
                    </div>
                </div>
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

            <section class="dashboard-panel friends-panel" id="amigos">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Amigos online</p>
                        <h2>Rede em foco</h2>
                    </div>

                    <a href="#" class="mini-link">Ver todos</a>
                </div>

                <div class="friend-list">
                    @foreach ($friends as $friend)
                        <article class="friend-item">
                            <div class="friend-avatar-wrap">
                                <span class="friend-avatar">{{ $friend['avatar'] }}</span>
                                <span class="friend-dot"></span>
                            </div>
                            <div class="friend-copy">
                                <strong>{{ $friend['name'] }}</strong>
                                <p>{{ $friend['status'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
@endsection
