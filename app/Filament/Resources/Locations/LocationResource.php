<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Lokasi';

    protected static ?string $modelLabel = 'lokasi';

    protected static ?string $pluralModelLabel = 'lokasi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Kode lokasi')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nama lokasi')->required()->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Lokasi')->searchable()->sortable(),
                TextColumn::make('items_count')->label('Jenis barang')->counts('items')->numeric()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLocations::route('/')];
    }
}
