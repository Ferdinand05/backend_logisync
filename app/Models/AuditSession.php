<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['audit_number', 'audit_method', 'location_id', 'category_id', 'item_ids', 'created_by', 'status'])]
class AuditSession extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['audit_number', 'audit_method', 'location_id', 'category_id', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('audit');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }

    protected function casts(): array
    {
        return ['item_ids' => 'array'];
    }
}
