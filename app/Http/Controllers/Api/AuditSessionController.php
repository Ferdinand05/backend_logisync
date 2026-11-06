<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanAuditItemRequest;
use App\Http\Resources\AuditItemResource;
use App\Http\Resources\AuditSessionResource;
use App\Models\AuditItem;
use App\Models\AuditSession;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuditSessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $sessions = AuditSession::query()
            ->with(['location:id,code,name', 'category:id,name'])
            ->withCount([
                'auditItems as items_total',
                'auditItems as items_scanned' => fn ($query) => $query->whereNotNull('actual_qty'),
            ])
            ->where('status', 'in_progress')
            ->orderByDesc('created_at')
            ->paginate(10);

        return AuditSessionResource::collection($sessions);
    }

    public function show(Request $request, AuditSession $auditSession): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'gudang'], true), 403);

        return response()->json(['data' => $auditSession->load(['auditItems.item', 'auditItems.location'])]);
    }

    public function items(Request $request, AuditSession $auditSession): AnonymousResourceCollection
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $auditItems = $auditSession->auditItems()
            ->with(['item:id,sku,name', 'location:id,code,name'])
            ->join('locations', 'audit_items.location_id', '=', 'locations.id')
            ->join('items', 'audit_items.item_id', '=', 'items.id')
            ->select('audit_items.*')
            ->orderBy('locations.name')
            ->orderBy('items.name')
            ->paginate(10);

        return AuditItemResource::collection($auditItems);
    }

    public function scan(ScanAuditItemRequest $request, AuditSession $auditSession): JsonResponse
    {
        $data = $request->validated();

        $auditItem = DB::transaction(function () use ($auditSession, $data): AuditItem {
            $session = AuditSession::query()->whereKey($auditSession->id)->lockForUpdate()->firstOrFail();

            if (! in_array($session->status, ['in_progress', 'reviewing'], true)) {
                throw ValidationException::withMessages(['audit_session' => 'Sesi audit tidak aktif.']);
            }

            $item = Item::query()->where('sku', $data['sku'])->lockForUpdate()->first();

            if ($item === null) {
                abort(404, 'Barang dengan SKU tersebut tidak ditemukan.');
            }

            if (! $item->is_under_audit) {
                throw ValidationException::withMessages(['sku' => 'Barang tidak sedang dibekukan untuk audit.']);
            }

            $auditItem = AuditItem::query()
                ->where('audit_session_id', $session->id)
                ->where('item_id', $item->id)
                ->where('location_id', $data['location_id'])
                ->lockForUpdate()
                ->first();

            if ($auditItem === null) {
                throw ValidationException::withMessages(['sku' => 'Kombinasi SKU dan lokasi tidak ada dalam sesi audit ini.']);
            }

            $auditItem->update([
                'actual_qty' => $data['actual_qty'],
                'variance' => $data['actual_qty'] - $auditItem->system_qty,
            ]);

            return $auditItem;
        });

        return response()->json(['data' => $auditItem->load(['item', 'location'])]);
    }
}
