<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MogStudy') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="app-shell {{ request()->routeIs('dashboard', 'profile.show') ? 'app-immersive' : '' }}">
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>

    @unless (request()->routeIs('dashboard', 'profile.show'))
        <header class="topbar">
            <div class="container topbar-inner">
                <a class="brand" href="{{ route('landing') }}">
                    <span class="brand-mark">M</span>
                    <span>
                        <strong>MogStudy</strong>
                        <small>GitHub de estudos</small>
                    </span>
                </a>

                <nav class="topbar-nav">
                    @auth
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                        <a href="{{ route('profile.show', auth()->user()) }}">Perfil</a>
                        <form action="{{ route('logout') }}" method="POST" class="inline-form">
                            @csrf
                            <button type="submit" class="ghost-button">Sair</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Entrar</a>
                        <a class="primary-button" href="{{ route('register') }}">Criar conta</a>
                    @endauth
                </nav>
            </div>
        </header>
    @endunless

    @if (session('status'))
        <div class="flash success flash-fixed">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash error flash-fixed">
            <strong>Revise os campos abaixo.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')

    @stack('scripts')
</body>
</html>
