@php
    $locations = $getRecord()->locations->sortBy('name');
    $totalStock = $locations->sum('pivot.quantity');
@endphp

<style>
    .item-locations-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        font-size: 14px;
        line-height: 1.4;
    }

    .item-locations-table th {
        padding: 8px 16px;
        background-color: #f9fafb;
        color: #374151;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-align: left;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
    }

    .item-locations-table th.is-numeric,
    .item-locations-table td.is-numeric {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .item-locations-table td {
        padding: 8px 16px;
        color: #111827;
        white-space: nowrap;
    }

    .item-locations-table td.is-muted {
        color: #6b7280;
    }

    .item-locations-table tbody tr + tr {
        border-top: 1px solid #e5e7eb;
    }

    .item-locations-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .item-locations-table tfoot td {
        background-color: #f9fafb;
        border-top: 1px solid #e5e7eb;
        font-weight: 600;
    }

    .item-locations-table td.is-empty {
        padding: 24px 16px;
        color: #6b7280;
        text-align: center;
        white-space: normal;
    }

    .dark .item-locations-table {
        border-color: #374151;
    }

    .dark .item-locations-table th,
    .dark .item-locations-table tfoot td {
        background-color: #1f2937;
        color: #d1d5db;
        border-color: #374151;
    }

    .dark .item-locations-table td {
        color: #f3f4f6;
    }

    .dark .item-locations-table td.is-muted,
    .dark .item-locations-table td.is-empty {
        color: #9ca3af;
    }

    .dark .item-locations-table tbody tr + tr {
        border-color: #374151;
    }

    .dark .item-locations-table tbody tr:hover {
        background-color: #111827;
    }
</style>

<table class="item-locations-table">
    <thead>
        <tr>
            <th scope="col">Lokasi</th>
            <th scope="col">Kode</th>
            <th scope="col" class="is-numeric">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($locations as $location)
            <tr>
                <td>{{ $location->name }}</td>
                <td class="is-muted">{{ $location->code }}</td>
                <td class="is-numeric">{{ $location->pivot->quantity }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="is-empty">Belum ada stok di lokasi manapun.</td>
            </tr>
        @endforelse
    </tbody>
    @if ($locations->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2">Total Stok</td>
                <td class="is-numeric">{{ $totalStock }}</td>
            </tr>
        </tfoot>
    @endif
</table>
