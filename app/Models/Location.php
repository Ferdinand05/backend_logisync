<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['code', 'name'])]
class Location extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('lokasi');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_location_stocks')
            ->withPivot(['id', 'quantity'])
            ->withTimestamps();
    }
}
