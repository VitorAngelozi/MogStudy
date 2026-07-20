<?php

namespace Database\Seeders;

use App\Models\DailyLog;
use App\Models\StudySession;
use App\Models\StudySubject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $vitor = User::create([
            'username' => 'vitorangelozi',
            'display_name' => 'Vitor Angelozi',
            'profile_title' => 'dev',
            'email' => 'vitor@example.com',
            'password' => bcrypt('12345678'),
            'bio' => 'Construindo consistencia em estudos de backend.',
        ]);

        $maria = User::create([
            'username' => 'mariasilva',
            'display_name' => 'Maria Silva',
            'profile_title' => 'analista de dados',
            'email' => 'maria@example.com',
            'password' => bcrypt('12345678'),
            'bio' => 'Registro de estudos, foco e disciplina.',
        ]);

        $joao = User::create([
            'username' => 'joaosantos',
            'display_name' => 'Joao Santos',
            'profile_title' => 'backend learner',
            'email' => 'joao@example.com',
            'password' => bcrypt('12345678'),
            'bio' => 'Perfil de exemplo para o MogStudy.',
        ]);

        $laravel = StudySubject::create([
            'user_id' => $vitor->id,
            'name' => 'Laravel',
            'description' => 'Backend, autenticacao e rotas.',
        ]);

        $arquitetura = StudySubject::create([
            'user_id' => $vitor->id,
            'name' => 'Arquitetura',
            'description' => 'Organizacao do projeto MogStudy.',
        ]);

        StudySession::create([
            'user_id' => $vitor->id,
            'study_subject_id' => $laravel->id,
            'subject' => 'Laravel',
            'notes' => 'Estudo sobre autenticacao e rotas.',
            'started_at' => now()->subDays(1)->setTime(19, 0),
            'ended_at' => now()->subDays(1)->setTime(20, 15),
            'duration_seconds' => 4500,
        ]);

        StudySession::create([
            'user_id' => $vitor->id,
            'study_subject_id' => $arquitetura->id,
            'subject' => 'Arquitetura',
            'notes' => 'Organizacao do projeto MogStudy.',
            'started_at' => now()->subDay()->setTime(21, 0),
            'ended_at' => now()->subDay()->setTime(21, 45),
            'duration_seconds' => 2700,
        ]);

        DailyLog::create([
            'user_id' => $vitor->id,
            'log_date' => now()->subDay()->toDateString(),
            'title' => 'Estudo de autenticacao',
            'content' => 'Hoje eu revisei o fluxo de login e estrutura de sessao no Laravel.',
            'study_minutes' => 120,
        ]);

        DailyLog::create([
            'user_id' => $vitor->id,
            'log_date' => now()->toDateString(),
            'title' => 'Migrando para MogStudy',
            'content' => 'Organizei o projeto para receber timer, perfil publico e feed diario.',
            'study_minutes' => 0,
        ]);

        DailyLog::create([
            'user_id' => $maria->id,
            'log_date' => now()->subDays(2)->toDateString(),
            'title' => 'Rotina minima',
            'content' => 'Uma sessao curta ainda conta. O importante e manter o ritmo.',
            'study_minutes' => 45,
        ]);

        DailyLog::create([
            'user_id' => $joao->id,
            'log_date' => now()->subDays(3)->toDateString(),
            'title' => 'Planejamento da semana',
            'content' => 'Separei os topicos de hoje e defini uma meta para cada um.',
            'study_minutes' => 60,
        ]);
    }
}
