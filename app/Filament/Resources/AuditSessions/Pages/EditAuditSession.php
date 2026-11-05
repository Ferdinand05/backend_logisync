<?php

namespace App\Filament\Resources\AuditSessions\Pages;

use App\Filament\Resources\AuditSessions\AuditSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuditSession extends EditRecord
{
    protected static string $resource = AuditSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
