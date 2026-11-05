<?php

namespace App\Http\Controllers;

use App\Models\AuditSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AuditSessionPdfController extends Controller
{
    public function __invoke(AuditSession $auditSession): Response
    {
        $session = $auditSession->load(['auditItems.item', 'auditItems.location', 'location', 'category', 'creator']);
        $rows = $session->auditItems->sortBy(fn ($row): string => ($row->location?->name ?? '').'|'.($row->item?->name ?? ''));
        $scanned = $rows->whereNotNull('actual_qty')->count();
        $varianceTotal = $rows->whereNotNull('variance')->sum('variance');

        $pdf = Pdf::loadView('pdf.audit-session', [
            'session' => $session,
            'rows' => $rows,
            'scanned' => $scanned,
            'pending' => $rows->count() - $scanned,
            'varianceTotal' => $varianceTotal,
            'statusLabel' => match ($session->status) {
                'in_progress' => 'Berjalan',
                'reviewing' => 'Ditinjau',
                'completed' => 'Selesai',
                default => ucfirst($session->status),
            },
            'methodLabel' => match ($session->audit_method) {
                'location' => 'Audit lokasi',
                'category' => 'Audit kategori',
                'manual' => 'Audit pilihan barang',
                default => $session->audit_method,
            },
            'targetLabel' => $session->location?->name ?? $session->category?->name ?? 'Semua lokasi terkait',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-opname-'.Str::slug($session->audit_number).'.pdf');
    }
}
