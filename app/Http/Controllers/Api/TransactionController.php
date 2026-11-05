<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StockInRequest;
use App\Http\Requests\Transaction\StockOutRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function in(StockInRequest $request): JsonResponse
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $transaction = $this->record($request, 'in');

        return response()->json([
            'message' => 'Barang masuk berhasil dicatat.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    public function out(StockOutRequest $request): JsonResponse
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $transaction = $this->record($request, 'out');

        return response()->json([
            'message' => 'Barang keluar berhasil dicatat.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $data = $request->validate([
            'type' => ['nullable', Rule::in(['in', 'out', 'adjustment'])],
        ]);

        $transactions = Transaction::query()
            ->with(['item', 'location', 'user'])
            ->when($data['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->latest()
            ->paginate(15);

        return TransactionResource::collection($transactions);
    }

    private function record(FormRequest $request, string $type): Transaction
    {
        return DB::transaction(function () use ($request, $type): Transaction {
            $item = Item::query()->where('sku', $request->validated('sku'))->lockForUpdate()->first();

            if ($item === null) {
                abort(404, 'Barang dengan SKU tersebut tidak ditemukan.');
            }

            if ($item->is_under_audit) {
                throw ValidationException::withMessages([
                    'sku' => ['Barang sedang dibekukan karena sesi Stock Opname aktif.'],
                ]);
            }

            $locationId = (int) $request->validated('location_id');
            $quantity = (int) $request->validated('qty');

            $stock = DB::table('item_location_stocks')
                ->where('item_id', $item->id)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if ($type === 'out') {
                if ($stock === null) {
                    throw ValidationException::withMessages([
                        'location_id' => ['Barang tidak terdaftar di lokasi ini.'],
                    ]);
                }

                if ((int) $stock->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'qty' => ["Stok tidak mencukupi. Stok tersedia: {$stock->quantity}."],
                    ]);
                }

                DB::table('item_location_stocks')
                    ->where('item_id', $item->id)
                    ->where('location_id', $locationId)
                    ->decrement('quantity', $quantity, ['updated_at' => now()]);
            } elseif ($stock === null) {
                DB::table('item_location_stocks')->insert([
                    'item_id' => $item->id,
                    'location_id' => $locationId,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('item_location_stocks')
                    ->where('item_id', $item->id)
                    ->where('location_id', $locationId)
                    ->increment('quantity', $quantity, ['updated_at' => now()]);
            }

            return Transaction::query()->create([
                'item_id' => $item->id,
                'location_id' => $locationId,
                'user_id' => $request->user()->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference_no' => $request->validated('reference_no'),
                'notes' => $request->validated('notes'),
            ])->load(['item', 'location', 'user']);
        });
    }
}
