<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['code', 'name'])]
class Location extends Model
{
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_location_stocks')
            ->withPivot(['id', 'quantity'])
            ->withTimestamps();
    }
}
