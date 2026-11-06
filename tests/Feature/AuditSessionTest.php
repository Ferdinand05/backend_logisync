<?php

namespace Tests\Feature;

use App\Actions\CompleteAuditSession;
use App\Actions\StartAuditSession;
use App\Models\AuditSession;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_audit_snapshots_zero_for_items_without_stock_pivot(): void
    {
        $admin = User::factory()->create();
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang']);

        $session = app(StartAuditSession::class)->handle([
            'audit_number' => 'AUD-1',
            'audit_method' => 'location',
            'location_id' => $location->id,
        ], (int) $admin->id);

        $this->assertDatabaseHas('audit_items', [
            'audit_session_id' => $session->id,
            'item_id' => $item->id,
            'location_id' => $location->id,
            'system_qty' => 0,
            'actual_qty' => null,
        ]);
        $this->assertTrue($item->fresh()->is_under_audit);
    }

    public function test_completion_adjusts_stock_once_and_unfreezes_item(): void
    {
        $admin = User::factory()->create();
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang', 'is_under_audit' => true]);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-2', 'audit_method' => 'location', 'location_id' => $location->id,
            'created_by' => $admin->id, 'status' => 'in_progress',
        ]);
        $auditItem = $session->auditItems()->create([
            'item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 0,
            'actual_qty' => 7, 'variance' => 7,
        ]);

        app(CompleteAuditSession::class)->handle($session, (int) $admin->id);

        $this->assertDatabaseHas('item_location_stocks', ['item_id' => $item->id, 'location_id' => $location->id, 'quantity' => 7]);
        $this->assertDatabaseHas('transactions', ['item_id' => $item->id, 'type' => 'adjustment', 'quantity' => 7]);
        $this->assertDatabaseHas('audit_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertFalse($item->fresh()->is_under_audit);
        $this->assertSame(7, $auditItem->fresh()->variance);

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_completion_rejects_unscanned_rows(): void
    {
        $admin = User::factory()->create();
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang']);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-3', 'audit_method' => 'location', 'location_id' => $location->id,
            'created_by' => $admin->id, 'status' => 'in_progress',
        ]);
        $session->auditItems()->create(['item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 0]);

        try {
            app(CompleteAuditSession::class)->handle($session, (int) $admin->id);
        } catch (ValidationException) {
            $this->assertDatabaseHas('audit_sessions', ['id' => $session->id, 'status' => 'in_progress']);
            $this->assertDatabaseMissing('transactions', ['notes' => 'Penyesuaian audit AUD-3']);

            return;
        }

        $this->fail('Expected completion validation to reject an unscanned row.');
    }

    public function test_scan_endpoint_resolves_item_from_sku_and_location(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-1',
            'name' => 'Barang',
            'is_under_audit' => true,
        ]);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-4', 'audit_method' => 'location', 'location_id' => $location->id,
            'created_by' => $gudang->id, 'status' => 'in_progress',
        ]);
        $auditItem = $session->auditItems()->create([
            'item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 5,
        ]);

        Sanctum::actingAs($gudang);

        $this->postJson("/api/audit-sessions/{$session->id}/scan", [
            'sku' => 'SKU-1',
            'location_id' => $location->id,
            'actual_qty' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.actual_qty', 0)
            ->assertJsonPath('data.variance', -5);

        $this->assertSame(0, $auditItem->fresh()->actual_qty);
    }

    public function test_scan_endpoint_returns_not_found_for_unknown_sku(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang', 'is_under_audit' => true]);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-5', 'audit_method' => 'location', 'location_id' => $location->id,
            'created_by' => $gudang->id, 'status' => 'in_progress',
        ]);
        $session->auditItems()->create(['item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 0]);

        Sanctum::actingAs($gudang);

        $this->postJson("/api/audit-sessions/{$session->id}/scan", [
            'sku' => 'SKU-TIDAK-ADA',
            'location_id' => $location->id,
            'actual_qty' => 1,
        ])->assertNotFound();
    }

    public function test_scan_endpoint_rejects_sku_location_outside_the_session(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $otherLocation = Location::query()->create(['code' => 'B1', 'name' => 'Rak B1']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang', 'is_under_audit' => true]);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-6', 'audit_method' => 'location', 'location_id' => $location->id,
            'created_by' => $gudang->id, 'status' => 'in_progress',
        ]);
        $session->auditItems()->create(['item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 0]);

        Sanctum::actingAs($gudang);

        $this->postJson("/api/audit-sessions/{$session->id}/scan", [
            'sku' => 'SKU-1',
            'location_id' => $otherLocation->id,
            'actual_qty' => 1,
        ])->assertStatus(422);
    }

    public function test_show_endpoint_accessible_by_gudang_role(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-7',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'created_by' => $gudang->id,
            'status' => 'in_progress',
        ]);

        Sanctum::actingAs($gudang);

        $this->getJson("/api/audit-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('data.audit_number', 'AUD-7');
    }

    public function test_show_endpoint_accessible_by_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-8',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'created_by' => $admin->id,
            'status' => 'in_progress',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/audit-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('data.audit_number', 'AUD-8');
    }
}
