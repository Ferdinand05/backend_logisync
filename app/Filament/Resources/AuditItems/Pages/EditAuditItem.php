<?php

namespace App\Filament\Resources\AuditItems\Pages;

use App\Filament\Resources\AuditItems\AuditItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuditItem extends EditRecord
{
    protected static string $resource = AuditItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
