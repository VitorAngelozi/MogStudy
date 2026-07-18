<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function showRegister()
    {
        return view('register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.required' => 'Digite seu e-mail.',
            'email.email' => 'Use um e-mail válido.',
            'password.required' => 'Digite sua senha.',
            'password.min' => 'A senha precisa ter ao menos 8 caracteres.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'E-mail ou senha inválidos.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', 'unique:users,username'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'bio' => ['nullable', 'string', 'max:240'],
        ], [
            'username.required' => 'Escolha um nome de usuário.',
            'username.unique' => 'Esse nome de usuário já está em uso.',
            'email.required' => 'Digite seu e-mail.',
            'email.unique' => 'Esse e-mail já está em uso.',
            'password.required' => 'Digite sua senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'display_name' => $data['display_name'] ?: null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'bio' => $data['bio'] ?: null,
            'readme_markdown' => $this->defaultReadme($data['username'], $data['display_name'] ?? null),
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function defaultReadme(string $username, ?string $displayName = null): string
    {
        $name = $displayName ?: $username;

        return <<<MD
# Olá, eu sou {$name}

Bem-vindo ao meu perfil no MogStudy.

## Sobre mim
- Estou construindo uma rotina de estudos consistente.
- Uso este perfil para acompanhar o que aprendi no dia.

## O que estou estudando
- Laravel
- Backend
- Organização de rotina

## Objetivos atuais
- Fechar uma sessão de estudo por dia.
- Escrever um resumo diário.
- Manter meu README sempre atualizado.
MD;
    }
}
