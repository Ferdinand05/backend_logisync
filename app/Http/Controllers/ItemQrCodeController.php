<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ItemQrCodeController extends Controller
{
    public function __invoke(Item $item): Response
    {
        $filename = 'qr-'.Str::slug($item->sku).'.svg';
        $svg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->generate($item->sku);

        return response((string) $svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
