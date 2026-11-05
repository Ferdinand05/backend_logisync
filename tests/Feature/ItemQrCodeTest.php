<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_route_returns_svg_named_after_the_item_sku(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create(['name' => 'Test']);
        $item = Item::query()->create(['category_id' => $category->id, 'sku' => 'SKU-1', 'name' => 'Barang']);

        $response = $this->actingAs($admin)->get(route('items.qr', $item));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/svg+xml');
        $this->assertStringContainsString('qr-sku-1.svg', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('<svg', (string) $response->getContent());
    }
}
