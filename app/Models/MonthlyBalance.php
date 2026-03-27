<?php

namespace App\Models;

use Database\Factories\MonthlyBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'amount', 'comment'])]
class MonthlyBalance extends Model
{
    /** @use HasFactory<MonthlyBalanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
        ];
    }
}
