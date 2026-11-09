<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['category_id', 'sku', 'name', 'qr_image_path', 'is_under_audit'])]
class Item extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['category_id', 'sku', 'name', 'is_under_audit'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('barang');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'item_location_stocks')
            ->withPivot(['id', 'quantity'])
            ->withTimestamps();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Accessor: Menjumlahkan stok dari semua lokasi
    public function getTotalStockAttribute()
    {
        return $this->locations->sum('pivot.quantity');
    }
}
