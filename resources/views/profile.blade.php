@extends('layouts.app')

@php
    $canEditProfile = auth()->id() === $profileUser->id;
    $profilePhotoUrl = $profileUser->profilePhotoUrl();
    $profileInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profileUser->displayName(), 0, 1));
@endphp

@section('content')
    <div class="profile-page">
        <section class="hero hero-profile">
            <div class="profile-hero-main">
                @if ($profilePhotoUrl)
                    <img class="profile-hero-avatar profile-hero-avatar-image" src="{{ $profilePhotoUrl }}" alt="Foto de {{ $profileUser->profileTitle() }}">
                @else
                    <div class="profile-hero-avatar">{{ $profileInitial }}</div>
                @endif

                <div class="hero-copy">
                    <p class="eyebrow">Perfil publico</p>
                    <h1>{{ $profileUser->profileTitle() }}</h1>
                    <p class="profile-username">{{ '@'.$profileUser->username }}</p>
                    <p class="lead">{{ $profileUser->bio ?: 'Esse usuario ainda nao adicionou uma bio.' }}</p>
                </div>
            </div>

            <aside class="hero-panel profile-stats-panel">
                <div class="mini-stat">
                    <span>Streak</span>
                    <strong>{{ $streak }} dia{{ $streak === 1 ? '' : 's' }}</strong>
                </div>
                <div class="mini-stat">
                    <span>Sessoes</span>
                    <strong>{{ $sessionsCount }} cadastrada{{ $sessionsCount === 1 ? '' : 's' }}</strong>
                </div>
                <div class="mini-stat">
                    <span>Feed</span>
                    <strong>{{ $logsCount }} registro{{ $logsCount === 1 ? '' : 's' }}</strong>
                </div>
            </aside>
        </section>

        @if ($canEditProfile)
            <section class="panel profile-editor-panel" id="editar">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Editar perfil</p>
                        <h2>Foto, titulo e bio</h2>
                    </div>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-edit-form">
                    @csrf
                    @method('PUT')

                    <label class="profile-photo-upload">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                        @if ($profilePhotoUrl)
                            <span class="profile-photo-tile" style="background-image: url('{{ $profilePhotoUrl }}')">
                                <span class="subject-upload-overlay">Trocar</span>
                            </span>
                        @else
                            <span class="profile-photo-tile profile-photo-tile-empty">
                                <span>{{ $profileInitial }}</span>
                            </span>
                        @endif
                        <span class="profile-photo-upload-copy">
                            <strong>Foto do perfil</strong>
                            <small>JPG, PNG ou WEBP ate 2 MB.</small>
                        </span>
                    </label>

                    <div class="profile-edit-fields">
                        <label>
                            <span>Titulo</span>
                            <input
                                type="text"
                                name="profile_title"
                                value="{{ old('profile_title', $profileUser->profile_title) }}"
                                maxlength="50"
                                placeholder="Ex: dev"
                                data-character-counter="profile-title-counter"
                            >
                            <small class="muted character-counter" id="profile-title-counter">0/50 caracteres</small>
                        </label>

                        <label>
                            <span>Bio</span>
                            <textarea
                                name="bio"
                                rows="4"
                                maxlength="500"
                                placeholder="Conte um pouco sobre voce..."
                                data-character-counter="profile-bio-counter"
                            >{{ old('bio', $profileUser->bio) }}</textarea>
                            <small class="muted character-counter" id="profile-bio-counter">0/500 caracteres</small>
                        </label>

                        <button type="submit" class="primary-button">Salvar perfil</button>
                    </div>
                </form>
            </section>
        @endif

    </div>
@endsection
