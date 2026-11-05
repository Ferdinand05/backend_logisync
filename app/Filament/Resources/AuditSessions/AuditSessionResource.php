<?php

namespace App\Filament\Resources\AuditSessions;

use App\Filament\Resources\AuditSessions\Pages\ListAuditSessions;
use App\Filament\Resources\AuditSessions\Pages\ViewAuditSession;
use App\Models\AuditSession;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditSessionResource extends Resource
{
    protected static ?string $model = AuditSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Stock opname';

    protected static ?string $modelLabel = 'sesi opname';

    protected static ?string $pluralModelLabel = 'sesi opname';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['location', 'category', 'creator']))
            ->columns([
                TextColumn::make('audit_number')->label('Nomor opname')->searchable()->sortable(),
                TextColumn::make('audit_method')->label('Metode')->formatStateUsing(fn (string $state): string => match ($state) {
                    'location' => 'Lokasi',
                    'category' => 'Kategori',
                    'manual' => 'Manual',
                    default => $state,
                }),
                TextColumn::make('location.name')->label('Target lokasi')->placeholder('—'),
                TextColumn::make('category.name')->label('Target kategori')->placeholder('—'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('creator.name')->label('Dibuat oleh')->placeholder('—'),
            ])
            ->recordUrl(fn (AuditSession $record): string => ViewAuditSession::getUrl(['record' => $record]))
            ->recordActions([
                Action::make('view')->label('Detail')->icon(Heroicon::OutlinedEye)->url(fn (AuditSession $record): string => ViewAuditSession::getUrl(['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditSessions::route('/'),
            'view' => ViewAuditSession::route('/{record}'),
        ];
    }
}
