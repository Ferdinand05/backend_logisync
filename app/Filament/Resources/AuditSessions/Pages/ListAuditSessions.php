<?php

namespace App\Filament\Resources\AuditSessions\Pages;

use App\Actions\StartAuditSession;
use App\Filament\Resources\AuditSessions\AuditSessionResource;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAuditSessions extends ListRecords
{
    protected static string $resource = AuditSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startAudit')
                ->visible(fn (): bool => auth()->user()?->role === 'admin')
                ->label('Buat sesi opname baru')
                ->icon(Heroicon::OutlinedPlay)
                ->schema([
                    TextInput::make('audit_number')->label('Nomor opname')->required()->maxLength(255)->unique('audit_sessions', 'audit_number'),
                    Select::make('audit_method')->label('Metode audit')->options(['location' => 'Lokasi / rak', 'category' => 'Kategori', 'manual' => 'Pilih manual'])->required()->live(),
                    Select::make('location_id')->label('Lokasi')->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->visible(fn ($get): bool => $get('audit_method') === 'location')->required(fn ($get): bool => $get('audit_method') === 'location'),
                    Select::make('category_id')->label('Kategori')->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->visible(fn ($get): bool => $get('audit_method') === 'category')->required(fn ($get): bool => $get('audit_method') === 'category'),
                    Select::make('item_ids')->label('Barang')->options(fn (): array => Item::query()->orderBy('name')->pluck('name', 'id')->all())->multiple()->searchable()->preload()->visible(fn ($get): bool => $get('audit_method') === 'manual')->required(fn ($get): bool => $get('audit_method') === 'manual'),
                    Select::make('manual_target_location_id')->label('Lokasi opname')->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->visible(fn ($get): bool => $get('audit_method') === 'manual')->required(fn ($get): bool => $get('audit_method') === 'manual'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Mulai sesi opname')
                ->modalDescription('Barang target akan dibekukan secara global sampai sesi diselesaikan.')
                ->action(function (array $data, StartAuditSession $startAuditSession): void {
                    $session = $startAuditSession->handle($data, (int) auth()->id());
                    $this->redirect(AuditSessionResource::getUrl('view', ['record' => $session]));
                }),
        ];
    }
}
