<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AuditOverview;
use App\Filament\Widgets\InventoryStats;
use App\Filament\Widgets\LowStockItems;
use App\Filament\Widgets\RecentTransactions;
use App\Filament\Widgets\StockByLocation;
use App\Filament\Widgets\TransactionFlow;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
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
}
