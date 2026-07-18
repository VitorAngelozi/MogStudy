@extends('layouts.app')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">MogStudy</p>
            <h1>Seu GitHub de estudos, com timer, diário e README próprio.</h1>
            <p class="lead">
                Cronometre sessões, registre o que estudou hoje e mantenha um perfil público
                com cara de repositório vivo.
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
                <strong>Disponível no feed</strong>
            </div>
            <div class="mini-stat">
                <span>README</span>
                <strong>Markdown renderizado</strong>
            </div>
        </aside>
    </section>

    <section class="feature-grid">
        <article class="feature-card">
            <p class="eyebrow">Cronômetro</p>
            <h2>Registre sessões reais.</h2>
            <p>Inicie e pare seus blocos de estudo e salve o tempo em minutos no histórico.</p>
        </article>

        <article class="feature-card">
            <p class="eyebrow">Feed diário</p>
            <h2>Um dia, uma entrada.</h2>
            <p>Os registros aparecem como contribuições, deixando claro a consistência da rotina.</p>
        </article>

        <article class="feature-card">
            <p class="eyebrow">README</p>
            <h2>Perfil com identidade.</h2>
            <p>Escreva em markdown e mostre sua jornada de estudo de forma pública.</p>
        </article>
    </section>
@endsection
