<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'Vitor Angelozi',
            'email' => 'vitor@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'username' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'username' => 'João Santos',
            'email' => 'joao@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'username' => 'Ana Costa',
            'email' => 'ana@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'username' => 'Lucas Oliveira',
            'email' => 'lucas@example.com',
            'password' => bcrypt('123456'),
        ]);
         User::create([
            'username' => 'Vitor Polenta',
            'email' => 'polenta@polenta.com',
            'password' => bcrypt('123456'),
        ]);
    }
}