<?php

namespace App\Filament\Resources\Items;

use App\Filament\Resources\Items\Pages\ManageItems;
use App\Filament\Resources\Items\RelationManagers\TransactionsRelationManager;
use App\Models\Item;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Barang';

    protected static ?string $modelLabel = 'barang';

    protected static ?string $pluralModelLabel = 'barang';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi dasar')->schema([
                TextInput::make('name')->label('Nama barang')->required()->maxLength(255),
                TextInput::make('sku')->label('SKU / kode QR')->required()->maxLength(255)->unique(ignoreRecord: true),
            ])->columns(2),
            Section::make('Kategori dan stok per lokasi')->schema([
                Select::make('category_id')->label('Kategori')->relationship('category', 'name')->searchable()->preload()->required(),
                Select::make('locations')
                    ->label('Lokasi stok awal')
                    ->relationship('locations', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]),
            Toggle::make('is_under_audit')->label('Dibekukan untuk audit')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['category'])->withSum('locations as stock_quantity', 'item_location_stocks.quantity'))
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
                TextColumn::make('qr')
                    ->label('QR')
                    ->html()
                    ->state(fn (Item $record): HtmlString => QrCode::format('svg')->size(56)->margin(0)->generate($record->sku)),
                TextColumn::make('name')->label('Barang')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->sortable(),
                TextColumn::make('stock_quantity')->label('Total stok')->numeric()->sortable(),
                TextColumn::make('is_under_audit')->label('Audit')->badge()->formatStateUsing(fn (bool $state): string => $state ? 'Dibekukan' : 'Aktif')->color(fn (bool $state): string => $state ? 'warning' : 'success'),
            ])
            ->recordActions([
                Action::make('downloadQr')
                    ->label('Unduh QR')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->tooltip('Unduh QR')
                    ->url(fn (Item $record): string => route('items.qr', $record)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [TransactionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageItems::route('/'),
        ];
    }
}
