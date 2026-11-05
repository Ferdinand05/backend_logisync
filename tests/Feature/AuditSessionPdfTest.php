<?php

namespace Tests\Feature;

use App\Models\AuditSession;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditSessionPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_print_audit_session_as_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang']);
        $session = AuditSession::query()->create([
            'audit_number' => 'AUD-PDF',
            'audit_method' => 'location',
            'location_id' => $location->id,
            'created_by' => $admin->id,
            'status' => 'in_progress',
        ]);
        $session->auditItems()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'system_qty' => 5,
            'actual_qty' => 3,
            'variance' => -2,
        ]);

        $response = $this->actingAs($admin)->get(route('audit-sessions.pdf', $session));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }
}
