<?php

namespace App\Filament\Resources\AuditItems;

use App\Filament\Resources\AuditItems\Pages\ListAuditItems;
use App\Models\AuditItem;
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

class AuditItemResource extends Resource
{
    protected static ?string $model = AuditItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'Barang opname';

    protected static ?string $modelLabel = 'barang opname';

    protected static ?string $pluralModelLabel = 'barang opname';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['auditSession', 'item', 'location'])->latest('created_at'))
            ->columns([
                TextColumn::make('auditSession.audit_number')->label('Sesi')->searchable(),
                TextColumn::make('item.name')->label('Barang')->searchable(),
                TextColumn::make('location.name')->label('Lokasi'),
                TextColumn::make('system_qty')->label('Stok sistem')->numeric(),
                TextColumn::make('actual_qty')->label('Hasil scan')->numeric()->placeholder('Belum discan'),
                TextColumn::make('variance')->label('Selisih')->numeric()->placeholder('—'),
                TextColumn::make('created_at')->label('Tanggal dibuat')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('auditSession')
                    ->label('Sesi audit')
                    ->relationship('auditSession', 'audit_number')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('Tanggal dibuat')
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
        return ['index' => ListAuditItems::route('/')];
    }
}
