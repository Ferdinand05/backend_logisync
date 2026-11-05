<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Locked;

class StockByLocation extends ChartWidget
{
    #[Locked]
    public ?int $locationId = null;

    protected ?string $heading = 'Stok per lokasi';

    protected ?string $description = 'Perbandingan total unit stok di setiap lokasi';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $locations = Location::query()
            ->leftJoin('item_location_stocks', 'locations.id', '=', 'item_location_stocks.location_id')
            ->when($this->locationId, fn ($query) => $query->where('locations.id', $this->locationId))
            ->select('locations.id', 'locations.name')
            ->selectRaw('COALESCE(SUM(item_location_stocks.quantity), 0) as stock_units')
            ->groupBy('locations.id', 'locations.name')
            ->orderBy('locations.name')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Unit stok',
                'data' => $locations->map(fn (Location $location): int => (int) ($location->stock_units ?? 0))->all(),
                'backgroundColor' => '#d97706',
            ]],
            'labels' => $locations->map(fn (Location $location): string => $location->name)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
