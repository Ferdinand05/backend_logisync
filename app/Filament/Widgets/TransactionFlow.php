<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;

class TransactionFlow extends ChartWidget
{
    #[Locked]
    public ?int $locationId = null;

    protected ?string $heading = 'Pergerakan stok';

    protected ?string $description = 'Transaksi masuk dan keluar selama 30 hari';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $days = collect(range(0, 29))->map(fn (int $offset): Carbon => $start->copy()->addDays($offset));
        $daily = Transaction::query()
            ->whereIn('type', ['in', 'out'])
            ->where('created_at', '>=', $start)
            ->when($this->locationId, fn ($query) => $query->where('location_id', $this->locationId))
            ->selectRaw("DATE(created_at) as day, SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as incoming, SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as outgoing")
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy(fn ($row): string => Carbon::parse($row->day)->toDateString());

        return [
            'datasets' => [
                ['label' => 'Masuk', 'data' => $days->map(fn (Carbon $day): int => (int) ($daily[$day->toDateString()]->incoming ?? 0))->all(), 'backgroundColor' => '#16a34a'],
                ['label' => 'Keluar', 'data' => $days->map(fn (Carbon $day): int => (int) ($daily[$day->toDateString()]->outgoing ?? 0))->all(), 'backgroundColor' => '#dc2626'],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('d M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
