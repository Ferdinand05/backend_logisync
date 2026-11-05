<?php

namespace App\Filament\Resources\AuditItems\Pages;

use App\Filament\Resources\AuditItems\AuditItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditItem extends CreateRecord
{
    protected static string $resource = AuditItemResource::class;
}
