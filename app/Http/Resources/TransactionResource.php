<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'reference_no' => $this->reference_no,
            'notes' => $this->notes,
            'item' => $this->whenLoaded('item', fn (): array => [
                'id' => $this->item->id,
                'sku' => $this->item->sku,
                'name' => $this->item->name,
            ]),
            'location' => $this->whenLoaded('location', fn (): array => [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ]),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
