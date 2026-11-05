<?php

namespace App\Filament\Resources\AuditSessions\Pages;

use App\Actions\CompleteAuditSession;
use App\Filament\Resources\AuditSessions\AuditSessionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditSession extends ViewRecord
{
    protected static string $resource = AuditSessionResource::class;

    protected string $view = 'filament.resources.audit-sessions.pages.view-audit-session';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('audit-sessions.pdf', $this->record))
                ->openUrlInNewTab(),
            Action::make('completeAudit')
                ->label('Selesaikan & sesuaikan stok')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()?->role === 'admin' && in_array($this->record->status, ['in_progress', 'reviewing'], true))
                ->action(function (CompleteAuditSession $completeAuditSession): void {
                    $completeAuditSession->handle($this->record, (int) auth()->id());
                    $this->record->refresh();
                }),
        ];
    }

    public function refreshAudit(): void
    {
        $this->record->refresh()->load(['auditItems.item', 'auditItems.location', 'location', 'category', 'creator']);
    }
}
