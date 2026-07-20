<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'email.email' => 'Use um e-mail valido.',
            'password.required' => 'Digite sua senha.',
            'password.min' => 'A senha precisa ter ao menos 8 caracteres.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'E-mail ou senha invalidos.',
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
            'bio' => ['nullable', 'string', 'max:500'],
        ], [
            'username.required' => 'Escolha um nome de usuario.',
            'username.unique' => 'Esse nome de usuario ja esta em uso.',
            'email.required' => 'Digite seu e-mail.',
            'email.unique' => 'Esse e-mail ja esta em uso.',
            'password.required' => 'Digite sua senha.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'display_name' => $data['display_name'] ?: null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'bio' => $data['bio'] ?: null,
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
}
