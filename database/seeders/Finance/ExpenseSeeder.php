<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedExpenses(User::where('email', 'lukas@example.com')->firstOrFail(), [
            'Subscriptions' => [
                'Netflix' => 14,
                'Spotify' => 11,
                'YouTube Premium' => 13,
                'iCloud' => 3,
                'ChatGPT' => 20,
                'GitHub Copilot' => 10,
            ],
            'Housing' => [
                'Rent' => 750,
                'Electricity' => 65,
                'Water' => 25,
                'Internet' => 30,
            ],
            'Transport' => [
                'Fuel' => 120,
                'Car insurance' => 45,
                'Public transport' => 30,
            ],
        ]);

        $this->seedExpenses(User::where('email', 'lucka@example.com')->firstOrFail(), [
            'Subscriptions' => [
                'Netflix' => 14,
                'Spotify' => 11,
            ],
            'Housing' => [
                'Rent' => 400,
                'Internet' => 30,
            ],
            'Transport' => [
                'Public transport' => 30,
            ],
        ]);
    }

    /**
     * @param  array<string, array<string, int>>  $expensesByCategory
     */
    private function seedExpenses(User $user, array $expensesByCategory): void
    {
        $position = 0;

        foreach ($expensesByCategory as $categoryName => $expenses) {
            $category = ExpenseCategory::firstOrCreate(['name' => $categoryName], ['position' => ++$position]);

            foreach ($expenses as $name => $amount) {
                Expense::firstOrCreate(
                    ['user_id' => $user->id, 'expense_category_id' => $category->id, 'name' => $name],
                    ['amount' => $amount],
                );
            }
        }
    }
}
