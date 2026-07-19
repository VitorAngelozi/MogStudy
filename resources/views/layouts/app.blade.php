<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MogStudy') }}</title>
    <script>
        (() => {
            const storedTheme = localStorage.getItem('mogstudy-theme');
            const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
            document.documentElement.dataset.theme = storedTheme || (prefersLight ? 'light' : 'dark');
        })();
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="app-shell {{ request()->routeIs('dashboard', 'profile.show') ? 'app-immersive' : '' }}">
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>

    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Alternar tema" aria-pressed="false">
        <span class="theme-toggle-icon theme-toggle-sun" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 3v2"></path>
                <path d="M12 19v2"></path>
                <path d="M3 12h2"></path>
                <path d="M19 12h2"></path>
                <path d="m5.6 5.6 1.4 1.4"></path>
                <path d="m17 17 1.4 1.4"></path>
                <path d="m18.4 5.6-1.4 1.4"></path>
                <path d="m7 17-1.4 1.4"></path>
            </svg>
        </span>
        <span class="theme-toggle-icon theme-toggle-moon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M20 14.4A7.8 7.8 0 0 1 9.6 4a8 8 0 1 0 10.4 10.4Z"></path>
            </svg>
        </span>
    </button>

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
                        <a href="{{ route('study-subjects.index') }}">Materias</a>
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
