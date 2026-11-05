<x-filament-panels::page>
    @php
        $session = $this->record->load(['auditItems.item', 'auditItems.location']);
        $rows = $session->auditItems->sortBy(fn ($row): string => ($row->location?->name ?? '').'|'.($row->item?->name ?? ''));
        $scanned = $rows->whereNotNull('actual_qty')->count();
        $pending = $rows->count() - $scanned;
        $varianceTotal = $rows->whereNotNull('variance')->sum('variance');
        $statusLabel = match ($session->status) {
            'in_progress' => 'Berjalan',
            'reviewing' => 'Ditinjau',
            'completed' => 'Selesai',
            default => ucfirst($session->status),
        };
        $methodLabel = match ($session->audit_method) {
            'location' => 'Audit lokasi',
            'category' => 'Audit kategori',
            'manual' => 'Audit pilihan barang',
            default => $session->audit_method,
        };
        $targetLabel = $session->location?->name ?? $session->category?->name ?? 'Semua lokasi terkait';
    @endphp

    <style>
        .audit-opname { display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem; }
        .audit-opname__meta { display: flex; flex-wrap: wrap; gap: 0.5rem 1.75rem; padding: 0.875rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #f9fafb; color: #6b7280; }
        .audit-opname__meta strong { color: #111827; font-weight: 600; }
        .audit-opname__state { display: inline-block; padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .audit-opname__state--active { background: #fef3c7; color: #92400e; }
        .audit-opname__state--done { background: #d1fae5; color: #065f46; }
        .audit-opname__wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
        .audit-opname__table { width: 100%; border-collapse: collapse; }
        .audit-opname__table th, .audit-opname__table td { padding: 0.625rem 0.75rem; border-bottom: 1px solid #f3f4f6; text-align: left; vertical-align: top; }
        .audit-opname__table th { background: #f3f4f6; color: #374151; font-weight: 600; white-space: nowrap; }
        .audit-opname__table tbody tr:last-child td { border-bottom: 0; }
        .audit-opname__table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .audit-opname__table .sku { color: #6b7280; font-family: ui-monospace, monospace; font-size: 0.75rem; }
        .audit-opname__table .variance--pos { color: #b45309; font-weight: 600; }
        .audit-opname__table .variance--neg { color: #b45309; font-weight: 600; }
        .audit-opname__table .variance--zero { color: #047857; font-weight: 600; }
        .audit-opname__table .scan--pending { color: #9ca3af; }
        .audit-opname__table .scan--done { color: #047857; font-weight: 600; }
        .audit-opname__empty { padding: 2rem 1rem; text-align: center; color: #6b7280; }
        .dark .audit-opname__meta { border-color: #374151; background: #1f2937; color: #9ca3af; }
        .dark .audit-opname__meta strong { color: #f9fafb; }
        .dark .audit-opname__wrap { border-color: #374151; }
        .dark .audit-opname__table th, .dark .audit-opname__table td { border-color: #374151; }
        .dark .audit-opname__table th { background: #1f2937; color: #d1d5db; }
        .dark .audit-opname__table td { color: #e5e7eb; }
        .dark .audit-opname__table .sku { color: #9ca3af; }
        .dark .audit-opname__table .variance--pos, .dark .audit-opname__table .variance--neg { color: #fbbf24; }
        .dark .audit-opname__table .variance--zero, .dark .audit-opname__table .scan--done { color: #34d399; }
    </style>

    <div class="audit-opname" @if (in_array($this->record->status, ['in_progress', 'reviewing'], true)) wire:poll.5s="refreshAudit" @endif>
        <div class="audit-opname__meta">
            <span>Status <strong class="audit-opname__state {{ $session->status === 'completed' ? 'audit-opname__state--done' : 'audit-opname__state--active' }}">{{ $statusLabel }}</strong></span>
            <span>Metode <strong>{{ $methodLabel }}</strong> · {{ $targetLabel }}</span>
            <span>Progres <strong>{{ $scanned }} / {{ $rows->count() }}</strong> barang</span>
            <span>Sisa scan <strong>{{ $pending }}</strong></span>
            <span>Total selisih <strong>{{ $varianceTotal > 0 ? '+' : '' }}{{ $varianceTotal }}</strong></span>
        </div>

        <div class="audit-opname__wrap">
            <table class="audit-opname__table">
                <thead>
                    <tr>
                        <th>Lokasi</th>
                        <th>SKU</th>
                        <th>Barang</th>
                        <th class="num">Stok sistem</th>
                        <th class="num">Hasil scan</th>
                        <th class="num">Selisih</th>
                        <th>Status scan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->location?->name ?? 'Lokasi dihapus' }}</td>
                            <td class="sku">{{ $row->item?->sku ?? '—' }}</td>
                            <td>{{ $row->item?->name ?? 'Barang dihapus' }}</td>
                            <td class="num">{{ $row->system_qty }}</td>
                            <td class="num">{{ $row->actual_qty ?? '—' }}</td>
                            <td class="num {{ $row->variance === null ? '' : ($row->variance === 0 ? 'variance--zero' : ($row->variance > 0 ? 'variance--pos' : 'variance--neg')) }}">{{ $row->variance === null ? '—' : ($row->variance > 0 ? '+' : '').$row->variance }}</td>
                            <td class="{{ $row->actual_qty === null ? 'scan--pending' : 'scan--done' }}">{{ $row->actual_qty === null ? 'Belum dipindai' : 'Dipindai' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="audit-opname__empty">Belum ada barang dalam sesi opname ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
