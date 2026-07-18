<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'username',
        'display_name',
        'email',
        'password',
        'bio',
        'readme_markdown',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function studySubjects(): HasMany
    {
        return $this->hasMany(StudySubject::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function displayName(): string
    {
        return $this->display_name ?: $this->username;
    }

    public function defaultReadmeTemplate(): string
    {
        $displayName = $this->displayName();

        return <<<MD
# Olá, eu sou {$displayName}

Bem-vindo ao meu perfil no MogStudy.

## Sobre mim
- Estou construindo consistência nos meus estudos.
- Aqui acompanho sessões, registros diários e progresso real.

## O que estou estudando
- Laravel
- Backend
- Organização de rotina

## Metas da semana
- Estudar todos os dias.
- Registrar o aprendizado no feed.
- Fechar pelo menos uma sessão por dia.
MD;
    }

    public function renderedReadme(): string
    {
        $markdown = $this->readme_markdown ?: $this->defaultReadmeTemplate();

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
