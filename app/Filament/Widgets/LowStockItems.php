<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class LowStockItems extends TableWidget
{
    #[Locked]
    public ?int $locationId = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stok kritis')
            ->description('Barang dengan stok maksimal 10 unit pada lokasi terpilih atau gabungan lokasi.')
            ->query(fn (): Builder => Item::query()
                ->select('items.*')
                ->addSelect([
                    'stock_quantity' => DB::table('item_location_stocks')
                        ->selectRaw('COALESCE(SUM(quantity), 0)')
                        ->whereColumn('item_location_stocks.item_id', 'items.id')
                        ->when($this->locationId, fn ($query) => $query->where('location_id', $this->locationId)),
                    'stock_location_names' => DB::table('item_location_stocks')
                        ->join('locations', 'locations.id', '=', 'item_location_stocks.location_id')
                        ->selectRaw("STRING_AGG(DISTINCT locations.name, ', ')")
                        ->whereColumn('item_location_stocks.item_id', 'items.id')
                        ->where('item_location_stocks.quantity', '>', 0)
                        ->when($this->locationId, fn ($query) => $query->where('item_location_stocks.location_id', $this->locationId)),
                ])
                ->with('category')
                ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM item_location_stocks WHERE item_location_stocks.item_id = items.id'.($this->locationId ? ' AND location_id = ?' : '').') <= 10', $this->locationId ? [$this->locationId] : [])
                ->orderBy('stock_quantity'))
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('name')->label('Barang')->searchable(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('stock_quantity')->label('Stok')->numeric()->badge()->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning'),
                TextColumn::make('stock_location_names')->label('Lokasi stok')->placeholder('Tidak ada stok'),
            ])
            ->defaultSort('stock_quantity')
            ->paginated([5, 10]);
    }
}
