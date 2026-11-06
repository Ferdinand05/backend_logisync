<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => $this->whenLoaded('item', fn (): array => [
                'id' => $this->item->id,
                'sku' => $this->item->sku,
                'name' => $this->item->name,
            ]),
            'location' => $this->whenLoaded('location', fn (): array => [
                'id' => $this->location->id,
                'code' => $this->location->code,
                'name' => $this->location->name,
            ]),
            'system_qty' => $this->system_qty,
            'actual_qty' => $this->actual_qty,
            'variance' => $this->variance,
            'is_scanned' => $this->actual_qty !== null,
        ];
    }
}
