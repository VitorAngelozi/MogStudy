<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Vitor Angelozi',
            'email' => 'vitor@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'name' => 'João Santos',
            'email' => 'joao@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'name' => 'Ana Costa',
            'email' => 'ana@example.com',
            'password' => bcrypt('123456'),
        ]);

        User::create([
            'name' => 'Lucas Oliveira',
            'email' => 'lucas@example.com',
            'password' => bcrypt('123456'),
        ]);
    }
}