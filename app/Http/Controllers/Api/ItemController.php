<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemDetailResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ItemController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:255'],
        ]);

        $cacheKey = "items:lookup:{$data['sku']}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return response()->json(json_decode($cached, true));
        }

        $item = Item::query()
            ->with(['category', 'locations'])
            ->where('sku', $data['sku'])
            ->first();

        if ($item === null) {
            abort(404, 'Barang dengan SKU tersebut tidak ditemukan.');
        }

        $json = (new ItemDetailResource($item))->response()->getContent();

        Cache::put($cacheKey, $json, now()->addMinutes(10));

        return response()->json(json_decode($json, true));
    }
}
