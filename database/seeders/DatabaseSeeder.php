<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Finance\MonthlyBalanceSeeder;
use Database\Seeders\Finance\StatementSeeder;
use Database\Seeders\Health\ExerciseSeeder;
use Database\Seeders\Health\WorkoutSessionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Lukas',
            'email' => 'lukas@example.com',
        ]);

        $this->call(MonthlyBalanceSeeder::class);
        $this->call(StatementSeeder::class);
        $this->call(ExerciseSeeder::class);
        $this->call(WorkoutSessionSeeder::class);
    }
}
