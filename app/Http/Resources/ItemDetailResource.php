<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'locations' => $this->whenLoaded('locations', fn (): array => $this->locations->map(fn ($location): array => [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'qty' => $location->pivot->quantity,
            ])->all()),
        ];
    }
}
