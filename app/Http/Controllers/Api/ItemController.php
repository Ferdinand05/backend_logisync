<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemDetailResource;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function lookup(Request $request): ItemDetailResource
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:255'],
        ]);

        $item = Item::query()
            ->with(['category', 'locations'])
            ->where('sku', $data['sku'])
            ->first();

        if ($item === null) {
            abort(404, 'Barang dengan SKU tersebut tidak ditemukan.');
        }

        return new ItemDetailResource($item);
    }
}
