<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['audit_session_id', 'item_id', 'location_id', 'system_qty', 'actual_qty', 'variance', 'notes'])]
class AuditItem extends Model
{
    public function auditSession(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
