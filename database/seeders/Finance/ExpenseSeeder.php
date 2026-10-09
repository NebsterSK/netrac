<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $expensesByCategory = [
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
        ];

        $position = 0;

        foreach ($expensesByCategory as $categoryName => $expenses) {
            $category = ExpenseCategory::firstOrCreate(['name' => $categoryName], ['position' => ++$position]);

            foreach ($expenses as $name => $amount) {
                Expense::firstOrCreate(
                    ['expense_category_id' => $category->id, 'name' => $name],
                    ['amount' => $amount],
                );
            }
        }
    }
}
