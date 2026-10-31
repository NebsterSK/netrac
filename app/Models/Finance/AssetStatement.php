<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Amount held in an asset as of a statement.
 *
 * @property int $id
 * @property int $asset_id
 * @property int $statement_id
 * @property int $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AssetStatement extends Pivot
{
    //
}
