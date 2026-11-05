<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_endpoint_returns_sorted_list(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        Location::query()->create(['code' => 'RAK-B', 'name' => 'Rak B']);
        Location::query()->create(['code' => 'RAK-A', 'name' => 'Rak A']);

        Sanctum::actingAs($gudang);

        $this->getJson('/api/locations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Rak A')
            ->assertJsonPath('data.1.name', 'Rak B');
    }

    public function test_item_lookup_returns_item_with_category_and_locations(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location1 = Location::query()->create(['code' => 'RAK-A1', 'name' => 'Rak A1']);
        $location2 = Location::query()->create(['code' => 'RAK-A2', 'name' => 'Rak A2']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-TEST', 'name' => 'Test Item']);
        $item->locations()->attach($location1->id, ['quantity' => 10]);
        $item->locations()->attach($location2->id, ['quantity' => 5]);

        Sanctum::actingAs($gudang);

        $this->getJson('/api/items/lookup?sku=SKU-TEST')
            ->assertOk()
            ->assertJsonPath('data.sku', 'SKU-TEST')
            ->assertJsonPath('data.category.name', 'Test')
            ->assertJsonCount(2, 'data.locations')
            ->assertJsonPath('data.locations.0.location_name', 'Rak A1')
            ->assertJsonPath('data.locations.0.qty', 10);
    }

    public function test_item_lookup_returns_not_found_for_unknown_sku(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);

        Sanctum::actingAs($gudang);

        $this->getJson('/api/items/lookup?sku=SKU-TIDAK-ADA')->assertNotFound();
    }

    public function test_location_items_returns_location_with_sorted_items(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $category = Category::query()->create(['name' => 'Test']);
        $location = Location::query()->create(['code' => 'RAK-A1', 'name' => 'Rak A1']);
        $item1 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-2', 'name' => 'Zebra']);
        $item2 = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Alpha']);
        $location->items()->attach($item1->id, ['quantity' => 8]);
        $location->items()->attach($item2->id, ['quantity' => 12]);

        Sanctum::actingAs($gudang);

        $this->getJson("/api/locations/{$location->id}/items")
            ->assertOk()
            ->assertJsonPath('data.code', 'RAK-A1')
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.name', 'Alpha')
            ->assertJsonPath('data.items.0.quantity', 12)
            ->assertJsonPath('data.items.1.name', 'Zebra');
    }

    public function test_endpoints_require_gudang_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/locations')->assertForbidden();
        $this->getJson('/api/items/lookup?sku=SKU-1')->assertForbidden();
    }
}
