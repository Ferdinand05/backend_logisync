<?php

namespace App\Actions;

use App\Models\AuditSession;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteAuditSession
{
    public function handle(AuditSession $auditSession, int $adminId): AuditSession
    {
        return DB::transaction(function () use ($auditSession, $adminId): AuditSession {
            $session = AuditSession::query()->whereKey($auditSession->id)->lockForUpdate()->firstOrFail();

            if (! in_array($session->status, ['in_progress', 'reviewing'], true)) {
                throw ValidationException::withMessages(['status' => 'Sesi audit tidak aktif.']);
            }

            $auditItems = $session->auditItems()->lockForUpdate()->get();

            if ($auditItems->isEmpty() || $auditItems->contains(fn ($row): bool => $row->actual_qty === null)) {
                throw ValidationException::withMessages(['actual_qty' => 'Semua stok fisik wajib dipindai sebelum sesi diselesaikan.']);
            }

            Item::query()->whereIn('id', $auditItems->pluck('item_id'))->orderBy('id')->lockForUpdate()->get();
            $lockedStocks = DB::table('item_location_stocks')
                ->whereIn('item_id', $auditItems->pluck('item_id'))
                ->whereIn('location_id', $auditItems->pluck('location_id'))
                ->orderBy('item_id')
                ->orderBy('location_id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($stock): string => $stock->item_id.':'.$stock->location_id);

            foreach ($auditItems as $auditItem) {
                $stock = $lockedStocks->get($auditItem->item_id.':'.$auditItem->location_id);
                $currentQuantity = (int) ($stock->quantity ?? 0);
                $variance = $auditItem->actual_qty - $auditItem->system_qty;

                if ($currentQuantity !== $auditItem->system_qty) {
                    throw ValidationException::withMessages(['stock' => 'Stok berubah setelah snapshot audit dibuat.']);
                }

                if ($auditItem->actual_qty < 0) {
                    throw ValidationException::withMessages(['actual_qty' => 'Stok fisik tidak boleh negatif.']);
                }

                if ($variance !== 0) {
                    $newQuantity = $currentQuantity + $variance;

                    if ($newQuantity < 0) {
                        throw ValidationException::withMessages(['actual_qty' => 'Penyesuaian menghasilkan stok negatif.']);
                    }

                    if ($stock === null) {
                        DB::table('item_location_stocks')->insert([
                            'item_id' => $auditItem->item_id,
                            'location_id' => $auditItem->location_id,
                            'quantity' => $newQuantity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('item_location_stocks')
                            ->where('item_id', $auditItem->item_id)
                            ->where('location_id', $auditItem->location_id)
                            ->update(['quantity' => $newQuantity, 'updated_at' => now()]);
                    }

                    Transaction::query()->create([
                        'item_id' => $auditItem->item_id,
                        'location_id' => $auditItem->location_id,
                        'user_id' => $adminId,
                        'type' => 'adjustment',
                        'quantity' => $variance,
                        'notes' => "Penyesuaian audit {$session->audit_number}",
                    ]);
                }

                $auditItem->update(['variance' => $auditItem->actual_qty - $auditItem->system_qty]);
            }

            $session->update(['status' => 'completed']);
            $itemIds = $auditItems->pluck('item_id')->unique()->all();
            $otherActiveItemIds = DB::table('audit_items')
                ->join('audit_sessions', 'audit_sessions.id', '=', 'audit_items.audit_session_id')
                ->whereIn('audit_items.item_id', $itemIds)
                ->where('audit_sessions.id', '!=', $session->id)
                ->whereIn('audit_sessions.status', ['in_progress', 'reviewing'])
                ->pluck('audit_items.item_id')
                ->unique()
                ->all();
            Item::query()->whereIn('id', $itemIds)->whereNotIn('id', $otherActiveItemIds)->update(['is_under_audit' => false]);

            return $session->refresh();
        });
    }
}
