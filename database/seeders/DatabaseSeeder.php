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
            'email' => 'vitor@example.com',
            'password' => bcrypt('12345678'),
            'bio' => 'Construindo consistência em estudos de backend.',
            'readme_markdown' => "# Vitor\n\nEstudando Laravel e backend todos os dias.",
        ]);

        $maria = User::create([
            'username' => 'mariasilva',
            'display_name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => bcrypt('12345678'),
            'bio' => 'Registro de estudos, foco e disciplina.',
            'readme_markdown' => "# Maria\n\nAprendizado com pequenas sessões diárias.",
        ]);

        $joao = User::create([
            'username' => 'joaosantos',
            'display_name' => 'João Santos',
            'email' => 'joao@example.com',
            'password' => bcrypt('12345678'),
            'bio' => 'Perfil de exemplo para o MogStudy.',
            'readme_markdown' => "# João\n\nDois blocos de estudo por dia, sem desculpas.",
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
            'notes' => 'Estudo sobre autenticação e rotas.',
            'started_at' => now()->subDays(1)->setTime(19, 0),
            'ended_at' => now()->subDays(1)->setTime(20, 15),
            'duration_seconds' => 4500,
        ]);

        StudySession::create([
            'user_id' => $vitor->id,
            'study_subject_id' => $arquitetura->id,
            'subject' => 'Arquitetura',
            'notes' => 'Organização do projeto MogStudy.',
            'started_at' => now()->subDay()->setTime(21, 0),
            'ended_at' => now()->subDay()->setTime(21, 45),
            'duration_seconds' => 2700,
        ]);

        DailyLog::create([
            'user_id' => $vitor->id,
            'log_date' => now()->subDay()->toDateString(),
            'title' => 'Estudo de autenticação',
            'content' => "Hoje eu revisei o fluxo de login e estrutura de sessão no Laravel.",
            'study_minutes' => 120,
        ]);

        DailyLog::create([
            'user_id' => $vitor->id,
            'log_date' => now()->toDateString(),
            'title' => 'Migrando para MogStudy',
            'content' => "Organizei o projeto para receber timer, README e feed diário.",
            'study_minutes' => 0,
        ]);

        DailyLog::create([
            'user_id' => $maria->id,
            'log_date' => now()->subDays(2)->toDateString(),
            'title' => 'Rotina mínima',
            'content' => "Uma sessão curta ainda conta. O importante é manter o ritmo.",
            'study_minutes' => 45,
        ]);

        DailyLog::create([
            'user_id' => $joao->id,
            'log_date' => now()->subDays(3)->toDateString(),
            'title' => 'Planejamento da semana',
            'content' => "Separei os tópicos de hoje e defini uma meta para cada um.",
            'study_minutes' => 60,
        ]);
    }
}
