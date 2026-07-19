@extends('layouts.app')

@section('content')
    <main class="page subjects-page">
        <section class="container subjects-hero">
            <div>
                <p class="eyebrow">Materias</p>
                <h1>Organize seus focos de estudo</h1>
                <p class="lead">Crie materias, ajuste metas e deixe o timer sempre pronto para o proximo bloco.</p>
            </div>

            <div class="subjects-hero-actions">
                <a href="{{ route('dashboard') }}" class="ghost-button">Voltar ao dashboard</a>
                <a href="#criar" class="primary-button">Criar materia</a>
            </div>
        </section>

        <section class="container subjects-workspace">
            <details class="dashboard-panel subject-editor-card subject-create-card" id="criar" @if ($errors->has('name') && old('return_to') === 'subjects' && ! old('editing_subject_id')) open @endif>
                <summary>
                    <span>
                        <small class="eyebrow">Nova materia</small>
                        <strong>Criar uma area de foco</strong>
                    </span>
                    <span class="mini-button">Abrir card</span>
                </summary>

                <form action="{{ route('study-subjects.store') }}" method="POST" enctype="multipart/form-data" class="subject-editor-form">
                    @csrf
                    <input type="hidden" name="return_to" value="subjects">

                    <div class="subject-expanded-head">
                        <label class="subject-photo-upload">
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                            <span class="subject-photo-tile subject-photo-tile-empty">
                                @include('partials.dashboard.icon', ['name' => 'upload', 'class' => 'icon-svg'])
                            </span>
                            <span class="subject-photo-upload-copy">
                                <strong>Foto da materia</strong>
                                <small>JPG, PNG ou WEBP ate 2 MB.</small>
                            </span>
                        </label>

                        <div>
                            <p class="eyebrow">Novo foco</p>
                            <h2>Criar materia</h2>
                            <p class="muted">Escolha uma imagem, defina uma meta e deixe tudo pronto para o timer.</p>
                        </div>
                    </div>

                    <div class="subject-editor-grid">
                        <label>
                            <span>Nome</span>
                            <input type="text" name="name" value="{{ old('name') }}" maxlength="50" placeholder="Ex: Laravel" required>
                        </label>

                        <label>
                            <span>Descricao</span>
                            <input type="text" name="description" value="{{ old('description') }}" maxlength="240" placeholder="Opcional, ate 240 caracteres">
                        </label>
                    </div>

                    <div class="subject-goal-fields">
                        <label>
                            <span>Meta</span>
                            <input type="number" name="goal_value" value="{{ old('goal_value') }}" min="0" step="0.25" placeholder="Ex: 2">
                        </label>

                        <label>
                            <span>Unidade</span>
                            <select name="goal_unit">
                                <option value="hours" @selected(old('goal_unit', 'hours') === 'hours')>Horas</option>
                                <option value="minutes" @selected(old('goal_unit') === 'minutes')>Minutos</option>
                            </select>
                        </label>

                        <label>
                            <span>Periodo</span>
                            <select name="goal_period">
                                <option value="daily" @selected(old('goal_period', 'daily') === 'daily')>Diaria</option>
                                <option value="weekly" @selected(old('goal_period') === 'weekly')>Semanal</option>
                            </select>
                        </label>
                    </div>

                    <button type="submit" class="primary-button full-width">Criar materia</button>
                </form>
            </details>

            <div class="subjects-page-heading">
                <div>
                    <p class="eyebrow">Biblioteca</p>
                    <h2>{{ count($subjects) }} materia{{ count($subjects) === 1 ? '' : 's' }} cadastrada{{ count($subjects) === 1 ? '' : 's' }}</h2>
                </div>
            </div>

            <div class="subject-card-grid">
                @forelse ($subjects as $subject)
                    <details class="dashboard-panel subject-editor-card subject-editor-maximized" id="materia-{{ $subject['id'] }}" @if (old('editing_subject_id') == $subject['id']) open @endif>
                        <summary class="subject-card-summary" aria-label="Editar {{ $subject['name'] }}">
                            <span class="subject-card-summary-content">
                                <span class="subject-card-main">
                                    @if ($subject['photo_url'])
                                        <img class="subject-card-photo" src="{{ $subject['photo_url'] }}" alt="Foto de {{ $subject['name'] }}">
                                    @else
                                        <span class="subject-card-icon subject-icon-{{ $subject['tone'] }}">
                                            @include('partials.dashboard.icon', ['name' => $subject['icon'], 'class' => 'icon-svg'])
                                        </span>
                                    @endif

                                    <span class="subject-card-copy">
                                        <span class="eyebrow">Materia</span>
                                        <strong>{{ $subject['name'] }}</strong>
                                        <span>{{ $subject['description'] ?: 'Sem descricao ainda.' }}</span>
                                    </span>

                                    <span class="subject-card-stat">
                                        <strong>{{ $subject['goal_progress'] }}%</strong>
                                        <span>{{ $subject['hours_label'] }}</span>
                                    </span>

                                    <button
                                        type="submit"
                                        form="delete-subject-{{ $subject['id'] }}"
                                        class="icon-button icon-button-danger"
                                        aria-label="Excluir {{ $subject['name'] }}"
                                        data-subject-delete
                                    >
                                        @include('partials.dashboard.icon', ['name' => 'trash', 'class' => 'icon-svg'])
                                    </button>

                                    <span class="icon-button" aria-hidden="true">
                                        @include('partials.dashboard.icon', ['name' => 'pencil', 'class' => 'icon-svg'])
                                    </span>
                                </span>

                                <span class="subject-card-progress">
                                    <span class="progress-track">
                                        <span class="progress-fill progress-fill-violet" style="width: {{ $subject['goal_progress'] }}%"></span>
                                    </span>
                                    <span>{{ $subject['goal_label'] }}</span>
                                </span>
                            </span>
                        </summary>

                        <form action="{{ route('study-subjects.update', $subject['id']) }}" method="POST" enctype="multipart/form-data" class="subject-editor-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="return_to" value="subjects">
                            <input type="hidden" name="editing_subject_id" value="{{ $subject['id'] }}">

                            <div class="subject-expanded-head">
                                <label class="subject-photo-upload">
                                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                                    @if ($subject['photo_url'])
                                        <span class="subject-photo-tile" style="background-image: url('{{ $subject['photo_url'] }}')">
                                            <span class="subject-upload-overlay">
                                                @include('partials.dashboard.icon', ['name' => 'upload', 'class' => 'icon-svg'])
                                            </span>
                                        </span>
                                    @else
                                        <span class="subject-photo-tile subject-photo-tile-empty">
                                            @include('partials.dashboard.icon', ['name' => 'upload', 'class' => 'icon-svg'])
                                        </span>
                                    @endif
                                    <span class="subject-photo-upload-copy">
                                        <strong>Trocar foto</strong>
                                        <small>JPG, PNG ou WEBP ate 2 MB.</small>
                                    </span>
                                </label>

                                <div>
                                    <p class="eyebrow">Editando materia</p>
                                    <h2>{{ $subject['name'] }}</h2>
                                    <p class="muted">{{ $subject['goal_label'] }} - {{ $subject['hours_label'] }}</p>
                                </div>
                            </div>

                            <div class="subject-editor-grid">
                                <label>
                                    <span>Nome</span>
                                    <input type="text" name="name" value="{{ old('editing_subject_id') == $subject['id'] ? old('name', $subject['name']) : $subject['name'] }}" maxlength="50" required>
                                </label>

                                <label>
                                    <span>Descricao</span>
                                    <input type="text" name="description" value="{{ old('editing_subject_id') == $subject['id'] ? old('description', $subject['description']) : $subject['description'] }}" maxlength="240" placeholder="Resumo curto da materia">
                                </label>
                            </div>

                            <div class="subject-goal-fields">
                                <label>
                                    <span>Meta</span>
                                    <input type="number" name="goal_value" value="{{ old('editing_subject_id') == $subject['id'] ? old('goal_value', $subject['goal_value']) : $subject['goal_value'] }}" min="0" step="0.25" placeholder="Ex: 2">
                                </label>

                                <label>
                                    <span>Unidade</span>
                                    <select name="goal_unit">
                                        @php($goalUnit = old('editing_subject_id') == $subject['id'] ? old('goal_unit', $subject['goal_unit']) : $subject['goal_unit'])
                                        <option value="hours" @selected($goalUnit === 'hours')>Horas</option>
                                        <option value="minutes" @selected($goalUnit === 'minutes')>Minutos</option>
                                    </select>
                                </label>

                                <label>
                                    <span>Periodo</span>
                                    <select name="goal_period">
                                        @php($goalPeriod = old('editing_subject_id') == $subject['id'] ? old('goal_period', $subject['goal_period'] ?? 'daily') : ($subject['goal_period'] ?? 'daily'))
                                        <option value="daily" @selected($goalPeriod === 'daily')>Diaria</option>
                                        <option value="weekly" @selected($goalPeriod === 'weekly')>Semanal</option>
                                    </select>
                                </label>
                            </div>

                            <button type="submit" class="secondary-button full-width">Salvar materia</button>
                        </form>

                        <form
                            action="{{ route('study-subjects.destroy', $subject['id']) }}"
                            method="POST"
                            id="delete-subject-{{ $subject['id'] }}"
                            class="inline-form"
                        >
                            @csrf
                            @method('DELETE')
                        </form>
                    </details>
                @empty
                    <div class="dashboard-panel empty-state subjects-empty">
                        <strong>Nenhuma materia cadastrada ainda.</strong>
                        <p>Abra o card de criacao acima para montar sua primeira area de foco.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
