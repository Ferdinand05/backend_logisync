<?php

namespace App\Filament\Widgets;

use App\Models\AuditSession;
use Filament\Widgets\Widget;

class AuditOverview extends Widget
{
    protected string $view = 'filament.widgets.audit-overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $sessions = AuditSession::query()
            ->with(['location', 'auditItems'])
            ->whereIn('status', ['in_progress', 'reviewing'])
            ->latest('updated_at')
            ->get();

        return [
            'sessions' => $sessions,
            'scannedItems' => $sessions->sum(fn (AuditSession $session): int => $session->auditItems->whereNotNull('actual_qty')->count()),
            'totalItems' => $sessions->sum(fn (AuditSession $session): int => $session->auditItems->count()),
        ];
    }
}
