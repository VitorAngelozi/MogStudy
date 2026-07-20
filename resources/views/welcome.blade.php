@extends('layouts.app')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">MogStudy</p>
            <h1>Seu GitHub de estudos, com timer, diario e perfil proprio.</h1>
            <p class="lead">
                Cronometre sessoes, registre o que estudou hoje e mantenha um perfil publico
                com foto, titulo e bio.
            </p>

            <div class="hero-actions">
                <a class="primary-button" href="{{ route('register') }}">Criar conta</a>
                <a class="secondary-button" href="{{ route('login') }}">Entrar</a>
            </div>
        </div>

        <aside class="hero-panel">
            <div class="mini-stat">
                <span>Timer</span>
                <strong>00:45:12</strong>
            </div>
            <div class="mini-stat">
                <span>Registro do dia</span>
                <strong>Disponivel no feed</strong>
            </div>
            <div class="mini-stat">
                <span>Perfil</span>
                <strong>Foto, titulo e bio</strong>
            </div>
        </aside>
    </section>

    <section class="feature-grid">
        <article class="feature-card">
            <p class="eyebrow">Cronometro</p>
            <h2>Registre sessoes reais.</h2>
            <p>Inicie e pare seus blocos de estudo e salve o tempo em minutos no historico.</p>
        </article>

        <article class="feature-card">
            <p class="eyebrow">Feed diario</p>
            <h2>Um dia, uma entrada.</h2>
            <p>Os registros aparecem como contribuicoes, deixando clara a consistencia da rotina.</p>
        </article>

        <article class="feature-card">
            <p class="eyebrow">Perfil</p>
            <h2>Uma identidade simples.</h2>
            <p>Mostre uma foto, um titulo curto e uma bio para sua jornada de estudo.</p>
        </article>
    </section>
@endsection
