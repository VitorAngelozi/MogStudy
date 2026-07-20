@extends('layouts.app')

@section('content')
    <div class="container study-groups-page">
        <section class="hero subjects-hero study-groups-hero">
            <div>
                <p class="eyebrow">Novo grupo</p>
                <h1>Crie uma comunidade de foco.</h1>
                <p class="lead">Use para turma, vestibular, curso, concurso ou qualquer objetivo compartilhado.</p>
            </div>

            <a href="{{ route('study-groups.index') }}" class="secondary-button">Ver grupos</a>
        </section>

        <section class="panel study-group-create-card">
            <form action="{{ route('study-groups.store') }}" method="POST" class="study-room-form">
                @csrf

                <label>
                    <span>Nome do grupo</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="120" placeholder="Ex: Medicina - UFMS" required>
                </label>

                <label>
                    <span>Descricao</span>
                    <textarea name="description" rows="4" maxlength="500" placeholder="Conte qual objetivo une esse grupo.">{{ old('description') }}</textarea>
                </label>

                <label>
                    <span>Visibilidade</span>
                    <select name="visibility" required>
                        <option value="public" @selected(old('visibility') === 'public')>Publico</option>
                        <option value="friends" @selected(old('visibility') === 'friends')>Privado para amigos</option>
                        <option value="password" @selected(old('visibility') === 'password')>Privado com senha</option>
                    </select>
                </label>

                <label>
                    <span>Senha do grupo</span>
                    <input type="password" name="password" maxlength="40" placeholder="Obrigatoria se escolher privado com senha">
                </label>

                <button type="submit" class="primary-button">Criar grupo</button>
            </form>
        </section>
    </div>
@endsection
