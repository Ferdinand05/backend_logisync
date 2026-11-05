<?php

namespace App\Filament\Resources\Items\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Riwayat transaksi';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('user.name')->label('Staf')->placeholder('—'),
                TextColumn::make('type')->label('Jenis')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'in' => 'Masuk',
                    'out' => 'Keluar',
                    'adjustment' => 'Penyesuaian',
                    default => $state,
                }),
                TextColumn::make('quantity')->label('Jumlah')->numeric(),
            ]);
    }
}
