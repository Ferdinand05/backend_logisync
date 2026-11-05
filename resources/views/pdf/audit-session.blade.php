<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Opname {{ $session->audit_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h1 { margin: 0 0 2px; font-size: 17px; }
        .subtitle { margin: 0 0 14px; color: #6b7280; font-size: 10px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { padding: 5px 8px; border: 1px solid #d1d5db; vertical-align: top; }
        .meta .label { width: 110px; background: #f3f4f6; color: #4b5563; font-weight: bold; }
        .meta .value { width: 40%; }
        .items { width: 100%; border-collapse: collapse; }
        .items th, .items td { padding: 4px 6px; border: 1px solid #d1d5db; text-align: left; vertical-align: top; }
        .items th { background: #f3f4f6; color: #374151; font-size: 9px; text-transform: uppercase; }
        .items tr:nth-child(even) td { background: #fafafa; }
        .num { text-align: right; white-space: nowrap; }
        .sku { color: #6b7280; font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; }
        .positive, .negative { color: #b45309; font-weight: bold; }
        .zero { color: #047857; font-weight: bold; }
        .pending { color: #9ca3af; }
        .done { color: #047857; font-weight: bold; }
        .empty { padding: 16px; text-align: center; color: #6b7280; }
        .footer { margin-top: 12px; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Laporan Stock Opname</h1>
    <p class="subtitle">{{ $session->audit_number }} · Dicetak {{ now()->format('d M Y, H:i') }}</p>

    <table class="meta">
        <tr>
            <td class="label">Status</td>
            <td class="value">{{ $statusLabel }}</td>
            <td class="label">Metode</td>
            <td class="value">{{ $methodLabel }} · {{ $targetLabel }}</td>
        </tr>
        <tr>
            <td class="label">Progres scan</td>
            <td class="value">{{ $scanned }} / {{ $rows->count() }} barang</td>
            <td class="label">Sisa scan</td>
            <td class="value">{{ $pending }} barang</td>
        </tr>
        <tr>
            <td class="label">Total selisih</td>
            <td class="value">{{ $varianceTotal > 0 ? '+' : '' }}{{ $varianceTotal }}</td>
            <td class="label">Dibuat oleh</td>
            <td class="value">{{ $session->creator?->name ?? '—' }} · {{ $session->created_at?->format('d M Y, H:i') ?? '—' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>No</th>
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
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $row->location?->name ?? 'Lokasi dihapus' }}</td>
                    <td class="sku">{{ $row->item?->sku ?? '—' }}</td>
                    <td>{{ $row->item?->name ?? 'Barang dihapus' }}</td>
                    <td class="num">{{ $row->system_qty }}</td>
                    <td class="num">{{ $row->actual_qty ?? '—' }}</td>
                    <td class="num {{ $row->variance === null ? '' : ($row->variance === 0 ? 'zero' : ($row->variance > 0 ? 'positive' : 'negative')) }}">
                        {{ $row->variance === null ? '—' : ($row->variance > 0 ? '+' : '').$row->variance }}
                    </td>
                    <td class="{{ $row->actual_qty === null ? 'pending' : 'done' }}">{{ $row->actual_qty === null ? 'Belum dipindai' : 'Dipindai' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">Belum ada barang dalam sesi opname ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Dokumen ini dihasilkan otomatis dari sistem inventaris Logisync.</p>
</body>
</html>
