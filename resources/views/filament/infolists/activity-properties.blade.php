@php
    $record = $getRecord();
    $changes = $record->attribute_changes ?? collect();
    $old = $changes->get('old');
    $attributes = $changes->get('attributes');
    $properties = $record->properties ?? collect();

    $format = function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '—';
        }

        return (string) $value;
    };
@endphp

<style>
    .activity-properties-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        font-size: 14px;
        line-height: 1.4;
    }

    .activity-properties-table th {
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

    .activity-properties-table td {
        padding: 8px 16px;
        color: #111827;
        vertical-align: top;
        word-break: break-word;
    }

    .activity-properties-table tbody tr + tr {
        border-top: 1px solid #e5e7eb;
    }

    .activity-properties-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .activity-properties-table td.is-key {
        color: #6b7280;
        font-weight: 600;
        white-space: nowrap;
    }

    .activity-properties-table td.is-empty {
        padding: 24px 16px;
        color: #6b7280;
        text-align: center;
    }

    .dark .activity-properties-table {
        border-color: #374151;
    }

    .dark .activity-properties-table th {
        background-color: #1f2937;
        color: #d1d5db;
        border-color: #374151;
    }

    .dark .activity-properties-table td {
        color: #f3f4f6;
    }

    .dark .activity-properties-table td.is-key,
    .dark .activity-properties-table td.is-empty {
        color: #9ca3af;
    }

    .dark .activity-properties-table tbody tr + tr {
        border-color: #374151;
    }

    .dark .activity-properties-table tbody tr:hover {
        background-color: #111827;
    }
</style>

@if (! empty($old) && ! empty($attributes))
    <table class="activity-properties-table">
        <thead>
            <tr>
                <th scope="col">Atribut</th>
                <th scope="col">Sebelum</th>
                <th scope="col">Sesudah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attributes as $key => $value)
                <tr>
                    <td class="is-key">{{ $key }}</td>
                    <td>{{ $format($old[$key] ?? null) }}</td>
                    <td>{{ $format($value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif (! empty($attributes))
    <table class="activity-properties-table">
        <thead>
            <tr>
                <th scope="col">Atribut</th>
                <th scope="col">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attributes as $key => $value)
                <tr>
                    <td class="is-key">{{ $key }}</td>
                    <td>{{ $format($value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif (! empty($old))
    <table class="activity-properties-table">
        <thead>
            <tr>
                <th scope="col">Atribut</th>
                <th scope="col">Nilai Sebelum Dihapus</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($old as $key => $value)
                <tr>
                    <td class="is-key">{{ $key }}</td>
                    <td>{{ $format($value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif ($properties->isNotEmpty())
    <table class="activity-properties-table">
        <thead>
            <tr>
                <th scope="col">Properti</th>
                <th scope="col">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($properties as $key => $value)
                <tr>
                    <td class="is-key">{{ $key }}</td>
                    <td>{{ $format($value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <table class="activity-properties-table">
        <tbody>
            <tr>
                <td class="is-empty">Tidak ada rincian perubahan pada aktivitas ini.</td>
            </tr>
        </tbody>
    </table>
@endif
