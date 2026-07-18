@extends('layouts.app')

@section('content')
    <section class="hero hero-profile">
        <div class="hero-copy">
            <p class="eyebrow">Perfil público</p>
            <h1>{{ $profileUser->displayName() }}</h1>
            <p class="lead">{{ $profileUser->bio ?: 'Esse usuário ainda não adicionou uma bio.' }}</p>
        </div>

        <aside class="hero-panel">
            <div class="mini-stat">
                <span>Streak</span>
                <strong>{{ $streak }} dia{{ $streak === 1 ? '' : 's' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Sessões</span>
                <strong>{{ $sessions->count() }} recentes</strong>
            </div>
            <div class="mini-stat">
                <span>Feed</span>
                <strong>{{ $logs->count() }} registros</strong>
            </div>
        </aside>
    </section>

    <section class="panel-grid">
        @include('partials.contribution-grid', ['heatmap' => $heatmap])

        <div class="panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">README</p>
                    <h2>{{ '@' . $profileUser->username }}</h2>
                </div>
            </div>

            <article class="markdown-body">
                {!! $profileUser->renderedReadme() !!}
            </article>
        </div>
    </section>

    <section class="panel-grid">
        <div class="panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Registros</p>
                    <h2>Últimos dias</h2>
                </div>
            </div>

            <div class="feed-list">
                @forelse ($logs as $log)
                    <article class="feed-item">
                        <div class="feed-meta">
                            <strong>{{ $log->title }}</strong>
                            <span>{{ $log->log_date->format('d/m/Y') }}</span>
                        </div>
                        <p>{{ $log->content }}</p>
                        <small>{{ $log->study_minutes }} min estudados</small>
                    </article>
                @empty
                    <p class="muted">Nenhum registro disponível.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Sessões</p>
                    <h2>Histórico recente</h2>
                </div>
            </div>

            <div class="session-list">
                @forelse ($sessions as $session)
                    <article class="session-item">
                        <div>
                            <strong>{{ $session->subject }}</strong>
                            <p class="muted">
                                {{ $session->started_at->format('d/m/Y H:i') }}
                                @if ($session->ended_at)
                                    - {{ $session->ended_at->format('H:i') }}
                                @endif
                            </p>
                        </div>
                        <span>{{ $session->duration_label }}</span>
                    </article>
                @empty
                    <p class="muted">Sem sessões para mostrar.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
