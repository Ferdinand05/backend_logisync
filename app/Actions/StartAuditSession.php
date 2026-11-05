<?php

namespace App\Actions;

use App\Models\AuditSession;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartAuditSession
{
    /** @param array{audit_number: string, audit_method: string, location_id?: int|null, category_id?: int|null, item_ids?: array<int>} $data */
    public function handle(array $data, int $adminId): AuditSession
    {
        return DB::transaction(function () use ($data, $adminId): AuditSession {
            $itemIds = Item::query()
                ->when($data['audit_method'] === 'category', fn ($query) => $query->where('category_id', $data['category_id']))
                ->when($data['audit_method'] === 'manual', fn ($query) => $query->whereIn('id', $data['item_ids']))
                ->when($data['audit_method'] === 'location', function ($query) use ($data): void {
                    $query->where(function ($locationItems) use ($data): void {
                        $locationItems->whereHas('locations', fn ($locations) => $locations->where('locations.id', $data['location_id']))
                            ->orWhereDoesntHave('locations');
                    });
                })
                ->orderBy('id')
                ->pluck('id');

            $lockedItems = Item::query()->whereKey($itemIds)->orderBy('id')->lockForUpdate()->get();
            $items = $lockedItems;

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['target' => 'Target audit tidak memiliki barang.']);
            }

            $itemIds = $lockedItems->modelKeys();
            $hasActiveOverlap = DB::table('audit_items')
                ->join('audit_sessions', 'audit_sessions.id', '=', 'audit_items.audit_session_id')
                ->whereIn('audit_items.item_id', $itemIds)
                ->whereIn('audit_sessions.status', ['in_progress', 'reviewing'])
                ->exists();

            if ($hasActiveOverlap || $lockedItems->contains(fn (Item $item): bool => $item->is_under_audit)) {
                throw ValidationException::withMessages(['target' => 'Satu atau lebih barang sudah dibekukan dalam sesi audit lain.']);
            }

            $snapshotLocationId = $data['audit_method'] === 'manual' ? (int) ($data['snapshot_location_id'] ?? 0) : null;
            if ($data['audit_method'] === 'manual' && $snapshotLocationId === 0) {
                throw ValidationException::withMessages(['snapshot_location_id' => 'Pilih lokasi untuk opname manual.']);
            }

            $session = AuditSession::query()->create([
                'audit_number' => $data['audit_number'],
                'audit_method' => $data['audit_method'],
                'location_id' => $data['audit_method'] === 'location' ? $data['location_id'] : ($data['audit_method'] === 'manual' ? $snapshotLocationId : null),
                'category_id' => $data['audit_method'] === 'category' ? $data['category_id'] : null,
                'item_ids' => $data['audit_method'] === 'manual' ? $data['item_ids'] : null,
                'created_by' => $adminId,
                'status' => 'in_progress',
            ]);

            $allLocationIds = $data['audit_method'] === 'location' || $snapshotLocationId !== null
                ? [(int) ($data['audit_method'] === 'location' ? $data['location_id'] : $snapshotLocationId)]
                : Location::query()->pluck('id')->all();
            $stockRows = DB::table('item_location_stocks')
                ->whereIn('item_id', $items->modelKeys())
                ->when($data['audit_method'] === 'location' || $snapshotLocationId !== null, fn ($query) => $query->where('location_id', $data['audit_method'] === 'location' ? $data['location_id'] : $snapshotLocationId))
                ->whereIn('location_id', $allLocationIds)
                ->orderBy('item_id')
                ->orderBy('location_id')
                ->lockForUpdate()
                ->get()
                ->groupBy('item_id');
            $snapshot = [];

            foreach ($items as $item) {
                $itemStocks = $stockRows->get($item->id, collect());
                $locations = $itemStocks->isNotEmpty()
                    ? $itemStocks
                    : collect($allLocationIds)->map(fn ($locationId): object => (object) ['location_id' => $locationId, 'quantity' => 0]);

                if ($data['audit_method'] === 'location' && $itemStocks->isEmpty()) {
                    $locations = collect([(object) ['location_id' => $data['location_id'], 'quantity' => 0]]);
                }

                foreach ($locations as $stock) {
                    $snapshot[] = [
                        'audit_session_id' => $session->id,
                        'item_id' => $item->id,
                        'location_id' => $stock->location_id,
                        'system_qty' => $stock->quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('audit_items')->insert($snapshot);
            Item::query()->whereIn('id', $items->modelKeys())->update(['is_under_audit' => true]);

            return $session;
        });
    }
}
