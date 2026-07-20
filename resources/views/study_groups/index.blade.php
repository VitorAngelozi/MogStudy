@extends('layouts.app')

@section('content')
    <div class="container study-groups-page">
        <section class="hero subjects-hero study-groups-hero">
            <div>
                <p class="eyebrow">Grupos de estudo</p>
                <h1>Estude com pessoas que querem chegar no mesmo lugar.</h1>
                <p class="lead">Entre em comunidades permanentes, escolha uma sala de foco e marque presença com suas próprias matérias.</p>
            </div>

            <div class="subjects-hero-actions">
                <a href="{{ route('dashboard') }}" class="secondary-button">Voltar ao dashboard</a>
                <a href="{{ route('study-groups.create') }}" class="primary-button">Criar grupo</a>
            </div>
        </section>

        @if ($activeParticipation)
            <section class="panel group-active-banner">
                <div>
                    <p class="eyebrow">Estudando agora</p>
                    <h2>Voce esta estudando {{ $activeParticipation->studySubject->name }} em {{ $activeParticipation->focusRoom->name }}</h2>
                    <p>{{ $activeParticipation->focusRoom->group->name }} · desde {{ $activeParticipation->started_at->format('H:i') }}</p>
                </div>
                <a href="{{ route('study-groups.show', ['studyGroup' => $activeParticipation->focusRoom->group, 'room' => $activeParticipation->focusRoom->id]) }}" class="primary-button">Abrir sala</a>
            </section>
        @endif

        <section class="study-group-workspace">
            <article class="panel">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Meus grupos</p>
                        <h2>Comunidades que voce participa</h2>
                    </div>
                </div>

                <div class="study-group-grid">
                    @forelse ($groups as $group)
                        <a href="{{ route('study-groups.show', $group) }}" class="study-group-card">
                            <span class="study-group-mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($group->name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ $group->name }}</strong>
                                <small>{{ $group->members_count }} membros · {{ $group->focus_rooms_count }} salas · {{ $group->visibilityLabel() }}</small>
                            </span>
                            <span class="study-room-code">{{ $group->code }}</span>
                        </a>
                    @empty
                        <div class="empty-state">
                            <strong>Voce ainda nao participa de nenhum grupo.</strong>
                            <p>Crie um grupo para sua turma ou explore grupos publicos abaixo.</p>
                        </div>
                    @endforelse
                </div>
            </article>

            <aside class="study-group-side">
                <article class="panel">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Buscar grupos</p>
                            <h2>Nome ou codigo</h2>
                        </div>
                    </div>

                    <form action="{{ route('study-groups.index') }}" method="GET" class="study-room-form">
                        <label>
                            <span>Busca</span>
                            <input type="text" name="group_search" value="{{ $search }}" maxlength="120" placeholder="Ex: UFMS ou ABC12345">
                        </label>
                        <button type="submit" class="primary-button full-width">Pesquisar</button>
                    </form>
                </article>

                @if ($search !== '')
                    <article class="panel">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Resultados</p>
                                <h2>Grupos encontrados</h2>
                            </div>
                        </div>

                        <div class="study-group-mini-list">
                            @forelse ($searchResults as $group)
                                <article class="study-group-mini">
                                    <strong>{{ $group->name }}</strong>
                                    <small>{{ $group->code }} Â· {{ $group->visibilityLabel() }} Â· {{ $group->members_count }} membros</small>

                                    @if ($group->members->contains('user_id', auth()->id()))
                                        <a href="{{ route('study-groups.show', $group) }}" class="secondary-button full-width">Abrir grupo</a>
                                    @elseif ($group->visibility === \App\Models\StudyGroup::VISIBILITY_PASSWORD)
                                        <form action="{{ route('study-groups.join', $group) }}" method="POST" class="study-room-form compact-password-form">
                                            @csrf
                                            <label>
                                                <span>Senha</span>
                                                <input type="password" name="password" required>
                                            </label>
                                            <button type="submit" class="primary-button full-width">Entrar</button>
                                        </form>
                                    @else
                                        <form action="{{ route('study-groups.join', $group) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="primary-button full-width">Entrar</button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <p class="muted">Nenhum grupo encontrado com essa busca.</p>
                            @endforelse
                        </div>
                    </article>
                @endif

                <article class="panel">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Entrar por codigo</p>
                            <h2>Recebeu um convite?</h2>
                        </div>
                    </div>

                    <form action="{{ route('study-groups.join-by-code') }}" method="POST" class="study-room-form">
                        @csrf
                        <label>
                            <span>Codigo do grupo</span>
                            <input type="text" name="code" value="{{ old('code', $joinCode) }}" maxlength="12" placeholder="Ex: A1B2C3D4" required>
                        </label>
                        <label>
                            <span>Senha, se houver</span>
                            <input type="password" name="password" maxlength="40" placeholder="Somente para grupos privados">
                        </label>
                        <button type="submit" class="primary-button full-width">Entrar no grupo</button>
                    </form>
                </article>

                <article class="panel">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Publicos</p>
                            <h2>Explorar grupos</h2>
                        </div>
                    </div>

                    <div class="study-group-mini-list">
                        @forelse ($publicGroups as $group)
                            <a href="{{ route('study-groups.show', $group) }}" class="study-group-mini">
                                <strong>{{ $group->name }}</strong>
                                <small>{{ $group->members_count }} membros · {{ $group->focus_rooms_count }} salas</small>
                            </a>
                        @empty
                            <p class="muted">Nenhum grupo publico disponivel agora.</p>
                        @endforelse
                    </div>
                </article>
            </aside>
        </section>
    </div>
@endsection
