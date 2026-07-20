@extends('layouts.app')

@section('content')
    <section class="auth-shell">
        <div class="auth-card">
            <p class="eyebrow">Cadastro</p>
            <h1>Criar conta no MogStudy</h1>

            <form action="{{ route('register.store') }}" method="POST" class="form-stack">
                @csrf

                <label>
                    <span>Username</span>
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="seuusername"
                        required
                        class="@error('username') is-invalid @enderror"
                        aria-invalid="@error('username') true @else false @enderror"
                    >
                    @error('username')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Nome de exibição</span>
                    <input type="text" name="display_name" value="{{ old('display_name') }}" placeholder="Seu nome">
                </label>

                <label>
                    <span>E-mail</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="voce@exemplo.com" required>
                </label>

                <label>
                    <span>Bio</span>
                    <textarea name="bio" rows="3" maxlength="500" placeholder="Conte um pouco sobre sua rotina">{{ old('bio') }}</textarea>
                </label>

                <label>
                    <span>Senha</span>
                    <input type="password" name="password" placeholder="Crie uma senha" required>
                </label>

                <label>
                    <span>Confirmar senha</span>
                    <input type="password" name="password_confirmation" placeholder="Repita a senha" required>
                </label>

                <button type="submit" class="primary-button full-width">Criar conta</button>
            </form>

            <p class="auth-note">
                Já tem uma conta?
                <a href="{{ route('login') }}">Entrar</a>
            </p>
        </div>
    </section>
@endsection
