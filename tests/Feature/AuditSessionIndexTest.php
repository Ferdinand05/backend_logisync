<?php

namespace Tests\Feature;

use App\Models\AuditSession;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditSessionIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Insert a session with an explicit created_at via DB (bypasses PostgreSQL DEFAULT now()).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function insertSession(array $attributes): AuditSession
    {
        $now = now();
        $defaults = [
            'audit_method' => 'location',
            'status' => 'in_progress',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $row = array_merge($defaults, $attributes);

        $id = DB::table('audit_sessions')->insertGetId($row);

        return AuditSession::findOrFail($id);
    }

    public function test_gudang_role_can_list_in_progress_sessions(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $location = Location::query()->create(['code' => 'GDG-A', 'name' => 'Gudang A']);
        $category = Category::query()->create(['name' => 'Elektronik']);

        $this->insertSession([
            'audit_number' => 'AUDIT-001',
            'location_id' => $location->id,
            'created_by' => $gudang->id,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);
        $this->insertSession([
            'audit_number' => 'AUDIT-002',
            'audit_method' => 'category',
            'category_id' => $category->id,
            'created_by' => $gudang->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.audit_number', 'AUDIT-002')
            ->assertJsonPath('data.1.audit_number', 'AUDIT-001');
    }

    public function test_only_in_progress_sessions_are_returned(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $location = Location::query()->create(['code' => 'GDG-A', 'name' => 'Gudang A']);

        $this->insertSession(['audit_number' => 'AUDIT-ACTIVE', 'location_id' => $location->id, 'created_by' => $gudang->id]);
        $this->insertSession(['audit_number' => 'AUDIT-DONE', 'location_id' => $location->id, 'created_by' => $gudang->id, 'status' => 'completed']);
        $this->insertSession(['audit_number' => 'AUDIT-REVIEW', 'location_id' => $location->id, 'created_by' => $gudang->id, 'status' => 'reviewing']);

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.audit_number', 'AUDIT-ACTIVE');
    }

    public function test_sessions_sorted_by_created_at_desc(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $location = Location::query()->create(['code' => 'GDG-A', 'name' => 'Gudang A']);

        $this->insertSession([
            'audit_number' => 'AUDIT-OLD',
            'location_id' => $location->id,
            'created_by' => $gudang->id,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $this->insertSession([
            'audit_number' => 'AUDIT-NEW',
            'location_id' => $location->id,
            'created_by' => $gudang->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertOk()
            ->assertJsonPath('data.0.audit_number', 'AUDIT-NEW')
            ->assertJsonPath('data.1.audit_number', 'AUDIT-OLD');
    }

    public function test_sessions_include_items_total_and_items_scanned_counts(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'GDG-A', 'name' => 'Gudang A']);
        $item1 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Item A']);
        $item2 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-2', 'name' => 'Item B']);
        $item3 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-3', 'name' => 'Item C']);

        $session = $this->insertSession([
            'audit_number' => 'AUDIT-001',
            'location_id' => $location->id,
            'created_by' => $gudang->id,
        ]);

        $session->auditItems()->createMany([
            ['item_id' => $item1->id, 'location_id' => $location->id, 'system_qty' => 10, 'actual_qty' => 10, 'variance' => 0],
            ['item_id' => $item2->id, 'location_id' => $location->id, 'system_qty' => 5, 'actual_qty' => null, 'variance' => null],
            ['item_id' => $item3->id, 'location_id' => $location->id, 'system_qty' => 8, 'actual_qty' => 7, 'variance' => -1],
        ]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertOk()
            ->assertJsonPath('data.0.items_total', 3)
            ->assertJsonPath('data.0.items_scanned', 2);
    }

    public function test_pagination_returns_10_items_per_page(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $location = Location::query()->create(['code' => 'GDG-A', 'name' => 'Gudang A']);

        for ($i = 1; $i <= 15; $i++) {
            $this->insertSession([
                'audit_number' => "AUDIT-{$i}",
                'location_id' => $location->id,
                'created_by' => $gudang->id,
            ]);
        }

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 10);

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions?page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_non_gudang_role_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertForbidden();
    }

    public function test_location_and_category_are_eager_loaded(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $location = Location::query()->create(['code' => 'GDG-A', 'name' => 'Gudang A']);
        $category = Category::query()->create(['name' => 'Elektronik']);

        $this->insertSession([
            'audit_number' => 'AUDIT-001',
            'location_id' => $location->id,
            'created_by' => $gudang->id,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);
        $this->insertSession([
            'audit_number' => 'AUDIT-002',
            'audit_method' => 'category',
            'category_id' => $category->id,
            'created_by' => $gudang->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($gudang, 'sanctum')
            ->getJson('/api/audit-sessions')
            ->assertOk()
            // data.0 = AUDIT-002 (newest, category method)
            ->assertJsonPath('data.0.audit_number', 'AUDIT-002')
            ->assertJsonPath('data.0.category.name', 'Elektronik')
            ->assertJsonPath('data.0.location', null)
            // data.1 = AUDIT-001 (older, location method)
            ->assertJsonPath('data.1.audit_number', 'AUDIT-001')
            ->assertJsonPath('data.1.location.name', 'Gudang A')
            ->assertJsonPath('data.1.category', null);
    }
}
