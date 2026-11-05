<?php

namespace Tests\Feature;

use App\Filament\Resources\Items\Pages\ManageItems;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemDetailModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_modal_renders_item_information_qr_and_location_breakdown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create(['name' => 'Elektronik']);
        $location = Location::query()->create(['code' => 'RAK-A1', 'name' => 'Rak A1']);
        $item = Item::query()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-MODAL',
            'name' => 'Barang Modal',
        ]);
        $item->locations()->attach($location->id, ['quantity' => 7]);

        $this->actingAs($admin);

        Livewire::test(ManageItems::class)
            ->mountAction(TestAction::make('view')->table($item))
            ->assertActionMounted(TestAction::make('view')->table($item))
            ->assertMountedActionModalSee([
                'Barang Modal',
                'SKU-MODAL',
                'Elektronik',
                'Rak A1',
                'Total Stok',
            ])
            ->assertMountedActionModalSeeHtml('<svg');
    }
}
