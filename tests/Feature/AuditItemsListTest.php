<?php

namespace Tests\Feature;

use App\Models\AuditSession;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditItemsListTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_role_can_list_audit_items(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Elektronik']);
        $location = Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);
        $item1 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Item A']);
        $item2 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-2', 'name' => 'Item B']);

        $session = AuditSession::query()->create([
            'audit_number' => 'AUDIT-001',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'status' => 'in_progress',
            'created_by' => $gudang->id,
        ]);

        $session->auditItems()->createMany([
            ['item_id' => $item1->id, 'location_id' => $location->id, 'system_qty' => 10, 'actual_qty' => 10, 'variance' => 0],
            ['item_id' => $item2->id, 'location_id' => $location->id, 'system_qty' => 5, 'actual_qty' => null, 'variance' => null],
        ]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.item.sku', 'SKU-1')
            ->assertJsonPath('data.0.is_scanned', true)
            ->assertJsonPath('data.1.item.sku', 'SKU-2')
            ->assertJsonPath('data.1.is_scanned', false);
    }

    public function test_items_sorted_by_location_name_then_item_name(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Elektronik']);
        $locationA = Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);
        $locationB = Location::query()->create(['code' => 'RAK-B', 'name' => 'Rak B']);
        $item1 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Zebra']);
        $item2 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-2', 'name' => 'Alpha']);
        $item3 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-3', 'name' => 'Beta']);

        $session = AuditSession::query()->create([
            'audit_number' => 'AUDIT-001',
            'audit_method' => 'manual',
            'status' => 'in_progress',
            'created_by' => $gudang->id,
            'item_ids' => [$item1->id, $item2->id, $item3->id],
        ]);

        $session->auditItems()->createMany([
            ['item_id' => $item1->id, 'location_id' => $locationB->id, 'system_qty' => 10],
            ['item_id' => $item2->id, 'location_id' => $locationA->id, 'system_qty' => 5],
            ['item_id' => $item3->id, 'location_id' => $locationA->id, 'system_qty' => 8],
        ]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items")
            ->assertOk()
            ->assertJsonPath('data.0.item.name', 'Alpha')
            ->assertJsonPath('data.0.location.name', 'Rak A')
            ->assertJsonPath('data.1.item.name', 'Beta')
            ->assertJsonPath('data.1.location.name', 'Rak A')
            ->assertJsonPath('data.2.item.name', 'Zebra')
            ->assertJsonPath('data.2.location.name', 'Rak B');
    }

    public function test_pagination_returns_10_items_per_page(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Elektronik']);
        $location = Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);

        $session = AuditSession::query()->create([
            'audit_number' => 'AUDIT-001',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'status' => 'in_progress',
            'created_by' => $gudang->id,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $item = Item::query()->create(['category_id' => $category->id, 'sku' => "SKU-{$i}", 'name' => "Item {$i}"]);
            $session->auditItems()->create(['item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 10]);
        }

        $this->actingAs($gudang, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 10);

        $this->actingAs($gudang, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items?page=2")
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_is_scanned_field_computed_correctly(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Elektronik']);
        $location = Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);
        // Names sorted: "Pending Item" < "Scanned Item" < "Zero Item"
        $itemScanned = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-SCANNED', 'name' => 'Scanned Item']);
        $itemPending = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-PENDING', 'name' => 'Pending Item']);
        $itemZero = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-ZERO', 'name' => 'Zero Item']);

        $session = AuditSession::query()->create([
            'audit_number' => 'AUDIT-001',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'status' => 'in_progress',
            'created_by' => $gudang->id,
        ]);

        $session->auditItems()->createMany([
            ['item_id' => $itemScanned->id, 'location_id' => $location->id, 'system_qty' => 10, 'actual_qty' => 8, 'variance' => -2],
            ['item_id' => $itemPending->id, 'location_id' => $location->id, 'system_qty' => 5, 'actual_qty' => null, 'variance' => null],
            ['item_id' => $itemZero->id, 'location_id' => $location->id, 'system_qty' => 3, 'actual_qty' => 0, 'variance' => -3],
        ]);

        // Sorted by item.name ASC: Pending Item (false), Scanned Item (true), Zero Item (true)
        $this->actingAs($gudang, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items")
            ->assertOk()
            ->assertJsonPath('data.0.item.name', 'Pending Item')
            ->assertJsonPath('data.0.is_scanned', false)
            ->assertJsonPath('data.1.item.name', 'Scanned Item')
            ->assertJsonPath('data.1.is_scanned', true)
            ->assertJsonPath('data.2.item.name', 'Zero Item')
            ->assertJsonPath('data.2.is_scanned', true);
    }

    public function test_non_gudang_role_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $location = Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUDIT-001',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'status' => 'in_progress',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items")
            ->assertForbidden();
    }

    public function test_item_and_location_are_eager_loaded(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Elektronik']);
        $location = Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Test Item']);

        $session = AuditSession::query()->create([
            'audit_number' => 'AUDIT-001',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'status' => 'in_progress',
            'created_by' => $gudang->id,
        ]);

        $session->auditItems()->create(['item_id' => $item->id, 'location_id' => $location->id, 'system_qty' => 10]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson("/api/audit-sessions/{$session->id}/items")
            ->assertOk()
            ->assertJsonPath('data.0.item.sku', 'SKU-1')
            ->assertJsonPath('data.0.item.name', 'Test Item')
            ->assertJsonPath('data.0.location.code', 'RAK-A')
            ->assertJsonPath('data.0.location.name', 'Rak A');
    }
}
