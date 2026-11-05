<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_creates_stock_pivot_and_transaction(): void
    {
        [$user, $location, $item] = $this->context();

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/in', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 10,
            'reference_no' => 'PO-2026-11-001',
            'notes' => 'Penerimaan barang dari supplier',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'in')
            ->assertJsonPath('data.quantity', 10)
            ->assertJsonPath('data.reference_no', 'PO-2026-11-001')
            ->assertJsonPath('data.item.sku', $item->sku);

        $this->assertDatabaseHas('item_location_stocks', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('transactions', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 10,
        ]);
    }

    public function test_stock_in_increments_existing_stock(): void
    {
        [$user, $location, $item] = $this->context();

        $item->locations()->attach($location->id, ['quantity' => 5]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/in', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 3,
            'reference_no' => 'PO-2026-11-002',
        ])->assertCreated();

        $this->assertDatabaseHas('item_location_stocks', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 8,
        ]);
    }

    public function test_stock_in_rejects_frozen_item(): void
    {
        [$user, $location, $item] = $this->context();
        $item->update(['is_under_audit' => true]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/in', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 5,
            'reference_no' => 'PO-2026-11-003',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseMissing('item_location_stocks', ['item_id' => $item->id]);
    }

    public function test_stock_in_returns_not_found_for_unknown_sku(): void
    {
        [$user, $location] = $this->context();

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/in', [
            'sku' => 'SKU-TIDAK-ADA',
            'location_id' => $location->id,
            'qty' => 5,
            'reference_no' => 'PO-2026-11-004',
        ])->assertNotFound();
    }

    public function test_stock_out_decrements_stock_and_records_transaction(): void
    {
        [$user, $location, $item] = $this->context();
        $item->locations()->attach($location->id, ['quantity' => 10]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/out', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 4,
            'reference_no' => 'SO-2026-11-001',
            'notes' => 'Pengeluaran pesanan pelanggan',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'out')
            ->assertJsonPath('data.quantity', 4);

        $this->assertDatabaseHas('item_location_stocks', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 6,
        ]);
        $this->assertDatabaseHas('transactions', [
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 4,
        ]);
    }

    public function test_stock_out_rejects_insufficient_stock(): void
    {
        [$user, $location, $item] = $this->context();
        $item->locations()->attach($location->id, ['quantity' => 2]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/out', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 5,
            'reference_no' => 'SO-2026-11-002',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['qty']);

        $this->assertDatabaseHas('item_location_stocks', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_stock_out_requires_an_existing_stock_pivot(): void
    {
        [$user, $location, $item] = $this->context();

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/out', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 1,
            'reference_no' => 'SO-2026-11-003',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['location_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_stock_out_rejects_frozen_item(): void
    {
        [$user, $location, $item] = $this->context();
        $item->locations()->attach($location->id, ['quantity' => 10]);
        $item->update(['is_under_audit' => true]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/out', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 1,
            'reference_no' => 'SO-2026-11-004',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_history_returns_all_transactions_and_supports_type_filter(): void
    {
        [$user, $location, $item] = $this->context();
        $otherUser = User::factory()->create(['role' => 'gudang']);

        Transaction::query()->create([
            'item_id' => $item->id, 'location_id' => $location->id, 'user_id' => $user->id,
            'type' => 'in', 'quantity' => 3, 'reference_no' => 'PO-1',
        ]);
        Transaction::query()->create([
            'item_id' => $item->id, 'location_id' => $location->id, 'user_id' => $otherUser->id,
            'type' => 'out', 'quantity' => 1, 'reference_no' => 'SO-1',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson('/api/transactions?type=out')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'out');
    }

    public function test_transaction_endpoints_require_gudang_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/transactions')->assertForbidden();
    }

    /**
     * @return array{User, Location, Item}
     */
    private function context(): array
    {
        $user = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'A1', 'name' => 'Rak A1']);
        $item = Item::query()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-1',
            'name' => 'Barang',
        ]);

        return [$user, $location, $item];
    }
}
