<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class RecentTransactions extends TableWidget
{
    #[Locked]
    public ?int $locationId = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aktivitas transaksi terbaru')
            ->query(fn (): Builder => Transaction::query()
                ->with(['item', 'location', 'user'])
                ->when($this->locationId, fn (Builder $query): Builder => $query->where('location_id', $this->locationId))
                ->latest())
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('user.name')->label('Petugas')->placeholder('Pengguna tidak tersedia'),
                TextColumn::make('item.sku')->label('SKU'),
                TextColumn::make('item.name')->label('Barang'),
                TextColumn::make('location.name')->label('Lokasi'),
                TextColumn::make('type')->label('Tindakan')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'in' => 'Masuk',
                    'out' => 'Keluar',
                    'adjustment' => 'Penyesuaian',
                    default => $state,
                })->color(fn (string $state): string => match ($state) {
                    'in' => 'success',
                    'out' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('quantity')->label('Jumlah')->numeric(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10]);
    }
}
