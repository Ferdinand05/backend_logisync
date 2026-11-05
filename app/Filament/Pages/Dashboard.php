<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AuditOverview;
use App\Filament\Widgets\InventoryStats;
use App\Filament\Widgets\LowStockItems;
use App\Filament\Widgets\RecentTransactions;
use App\Filament\Widgets\StockByLocation;
use App\Filament\Widgets\TransactionFlow;
use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected function getHeaderWidgets(): array
    {
        return [InventoryStats::class];
    }

    protected function getFooterWidgets(): array
    {
        return [
            TransactionFlow::class,
            StockByLocation::class,
            LowStockItems::class,
            AuditOverview::class,
            RecentTransactions::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return ['md' => 2, 'xl' => 2];
    }

    public function getWidgetData(): array
    {
        $locationId = $this->filters['locationId'] ?? null;
        $validated = Validator::make(['locationId' => $locationId], [
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
        ])->validate();

        return ['locationId' => $validated['locationId'] ?? null];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filter lokasi')
                ->schema([
                    Select::make('locationId')
                        ->label('Lokasi')
                        ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->placeholder('Semua lokasi')
                        ->selectablePlaceholder(),
                ])
                ->columns(1),
        ]);
    }
}
