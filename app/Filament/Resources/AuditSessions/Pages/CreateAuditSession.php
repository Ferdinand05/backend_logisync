<?php

namespace App\Filament\Resources\AuditSessions\Pages;

use App\Filament\Resources\AuditSessions\AuditSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditSession extends CreateRecord
{
    protected static string $resource = AuditSessionResource::class;
}
