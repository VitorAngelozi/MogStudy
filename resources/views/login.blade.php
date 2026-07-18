@extends('layouts.app')

@section('content')
    <section class="auth-shell">
        <div class="auth-card">
            <p class="eyebrow">Acesso</p>
            <h1>Entrar no MogStudy</h1>

            <form action="{{ route('login.attempt') }}" method="POST" class="form-stack">
                @csrf

                <label>
                    <span>E-mail</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="voce@exemplo.com" required>
                </label>

                <label>
                    <span>Senha</span>
                    <input type="password" name="password" placeholder="Sua senha" required>
                </label>

                <button type="submit" class="primary-button full-width">Entrar</button>
            </form>

            <p class="auth-note">
                Ainda não tem conta?
                <a href="{{ route('register') }}">Criar uma conta</a>
            </p>
        </div>
    </section>
@endsection
