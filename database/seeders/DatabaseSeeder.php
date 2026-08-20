<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'demo@msigroup.test'],
            ['name' => 'Usuario Demo', 'password' => 'password']
        );

        $this->call([
            RestauranteSeeder::class,
            ReservasDemoSeeder::class,
        ]);
    }
}
