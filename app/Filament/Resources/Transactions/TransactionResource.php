<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Transaction;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Riwayat transaksi';

    protected static ?string $modelLabel = 'transaksi';

    protected static ?string $pluralModelLabel = 'riwayat transaksi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['item', 'location', 'user']))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('user.name')->label('Staf')->placeholder('—'),
                TextColumn::make('item.name')->label('Barang'),
                TextColumn::make('location.name')->label('Lokasi'),
                TextColumn::make('type')->label('Jenis')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'in' => 'Masuk',
                    'out' => 'Keluar',
                    'adjustment' => 'Penyesuaian',
                    default => $state,
                }),
                TextColumn::make('quantity')->label('Jumlah')->numeric()->sortable(),
                TextColumn::make('reference_no')->label('No. Referensi')->searchable()->placeholder('—'),
                TextColumn::make('notes')->label('Catatan')->wrap()->placeholder('—')->limit(80),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis transaksi')
                    ->options([
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                        'adjustment' => 'Penyesuaian',
                    ]),
                SelectFilter::make('item')
                    ->label('Barang')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('Tanggal transaksi')
                    ->schema([
                        DatePicker::make('created_from')->label('Dari tanggal'),
                        DatePicker::make('created_until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTransactions::route('/')];
    }
}
