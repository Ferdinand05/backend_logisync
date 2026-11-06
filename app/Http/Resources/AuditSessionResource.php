<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audit_number' => $this->audit_number,
            'audit_method' => $this->audit_method,
            'status' => $this->status,
            'location' => $this->location_id ? [
                'id' => $this->location?->id,
                'code' => $this->location?->code,
                'name' => $this->location?->name,
            ] : null,
            'category' => $this->category_id ? [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ] : null,
            'created_at' => $this->created_at,
            'items_total' => $this->when(isset($this->items_total), $this->items_total),
            'items_scanned' => $this->when(isset($this->items_scanned), $this->items_scanned),
        ];
    }
}
