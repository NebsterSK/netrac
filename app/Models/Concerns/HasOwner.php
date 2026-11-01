<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For models that belong to exactly one user through `user_id`.
 *
 * @property int $user_id
 * @property-read User $owner
 */
trait HasOwner
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Limit to one owner; a null owner applies no constraint.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOwnedBy(Builder $query, ?int $ownerId): void
    {
        if ($ownerId !== null) {
            $query->where($query->qualifyColumn('user_id'), $ownerId);
        }
    }
}
