<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\Location;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class InventoryStats extends StatsOverviewWidget
{
    #[Locked]
    public ?int $locationId = null;

    protected function getStats(): array
    {
        $stockQuery = Item::query()->join('item_location_stocks', 'items.id', '=', 'item_location_stocks.item_id');

        if ($this->locationId !== null) {
            $stockQuery->where('item_location_stocks.location_id', $this->locationId);
        }

        $stockUnits = (clone $stockQuery)->sum('item_location_stocks.quantity');
        $lowStockCount = Item::query()
            ->select('items.id')
            ->join('item_location_stocks', 'items.id', '=', 'item_location_stocks.item_id')
            ->when($this->locationId, fn (Builder $query): Builder => $query->where('item_location_stocks.location_id', $this->locationId))
            ->groupBy('items.id')
            ->havingRaw('SUM(item_location_stocks.quantity) <= 10')
            ->get()
            ->count();

        return [
            Stat::make('Total barang', Item::query()->count()),
            Stat::make('Total lokasi', Location::query()->count()),
            Stat::make('Unit stok', number_format($stockUnits)),
            Stat::make('Stok kritis', $lowStockCount)
                ->description('Ambang global: 10 unit')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
