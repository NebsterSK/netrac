<?php

namespace App\Models;

use Database\Factories\StatementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property int $account
 * @property int $legacy_upgrade
 * @property int $uniqua_sds
 * @property int $uniqua_dds
 * @property int $finax
 * @property int $trading212
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\StatementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereFinax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereLegacyUpgrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereTrading212($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereUniquaDds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereUniquaSds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['date', 'account', 'legacy_upgrade', 'uniqua_sds', 'uniqua_dds', 'finax', 'trading212'])]
class Statement extends Model
{
    /** @use HasFactory<StatementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'account' => 'integer',
            'legacy_upgrade' => 'integer',
            'uniqua_sds' => 'integer',
            'uniqua_dds' => 'integer',
            'finax' => 'integer',
            'trading212' => 'integer',
        ];
    }
}
