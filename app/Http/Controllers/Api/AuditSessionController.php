<?php

namespace App\Http\Controllers\Api;

use App\Actions\CompleteAuditSession;
use App\Actions\StartAuditSession;
use App\Http\Controllers\Controller;
use App\Models\AuditItem;
use App\Models\AuditSession;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuditSessionController extends Controller
{
    public function store(Request $request, StartAuditSession $startAuditSession): JsonResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'audit_number' => ['required', 'string', 'max:255', 'unique:audit_sessions,audit_number'],
            'audit_method' => ['required', Rule::in(['location', 'category', 'manual'])],
            'location_id' => ['required_if:audit_method,location', 'nullable', 'integer', 'exists:locations,id'],
            'category_id' => ['required_if:audit_method,category', 'nullable', 'integer', 'exists:categories,id'],
            'item_ids' => ['required_if:audit_method,manual', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct', 'exists:items,id'],
            'manual_target_location_id' => ['required_if:audit_method,manual', 'nullable', 'integer', 'exists:locations,id'],
        ]);
        $data['snapshot_location_id'] = $data['manual_target_location_id'] ?? null;

        $session = $startAuditSession->handle($data, (int) $request->user()->id);

        return response()->json(['data' => $session->load('auditItems.item', 'auditItems.location')], 201);
    }

    public function scan(Request $request, AuditSession $auditSession): JsonResponse
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'actual_qty' => ['required', 'integer', 'min:0'],
        ]);

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

    public function show(Request $request, AuditSession $auditSession): JsonResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        return response()->json(['data' => $auditSession->load(['auditItems.item', 'auditItems.location'])]);
    }

    public function complete(Request $request, AuditSession $auditSession, CompleteAuditSession $completeAuditSession): JsonResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        return response()->json(['data' => $completeAuditSession->handle($auditSession, (int) $request->user()->id)]);
    }
}
