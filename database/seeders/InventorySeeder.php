<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Smartphone' => 'Telepon seluler dan tablet.',
            'Aksesoris' => 'Aksesoris pendukung perangkat.',
            'Audio' => 'Perangkat audio dan headset.',
            'Komputer' => 'Laptop, monitor, dan perangkat komputer.',
            'Jaringan' => 'Perangkat jaringan dan konektivitas.',
        ];

        $locations = [
            'RAK-A1' => 'Rak A1',
            'RAK-A2' => 'Rak A2',
            'RAK-A3' => 'Rak A3',
            'RAK-A4' => 'Rak A4',
            'RAK-A5' => 'Rak A5',
        ];

        $itemsByCategory = [
            'Smartphone' => [
                ['sku' => 'SKU-REDMI-N15', 'name' => 'Redmi Note 15', 'quantity' => 25],
                ['sku' => 'SKU-REDMI-N14', 'name' => 'Redmi Note 14', 'quantity' => 18],
                ['sku' => 'SKU-XIAOMI-14', 'name' => 'Xiaomi 14', 'quantity' => 10],
                ['sku' => 'SKU-POCO-X7', 'name' => 'Poco X7', 'quantity' => 14],
                ['sku' => 'SKU-SAMSUNG-A55', 'name' => 'Samsung Galaxy A55', 'quantity' => 9],
            ],
            'Aksesoris' => [
                ['sku' => 'SKU-CHARGER-33W', 'name' => 'Charger 33W', 'quantity' => 60],
                ['sku' => 'SKU-CASE-REDMI', 'name' => 'Case Redmi Note', 'quantity' => 80],
                ['sku' => 'SKU-TEMPERED-GLASS', 'name' => 'Tempered Glass Universal', 'quantity' => 120],
                ['sku' => 'SKU-MOUSE-WIRELESS', 'name' => 'Mouse Wireless', 'quantity' => 40],
                ['sku' => 'SKU-POWERBANK-10000', 'name' => 'Power Bank 10000mAh', 'quantity' => 35],
            ],
            'Audio' => [
                ['sku' => 'SKU-EARPHONE-BT', 'name' => 'Earphone Bluetooth', 'quantity' => 45],
                ['sku' => 'SKU-HEADSET-GAMING', 'name' => 'Headset Gaming', 'quantity' => 30],
                ['sku' => 'SKU-SPEAKER-BT', 'name' => 'Speaker Bluetooth', 'quantity' => 22],
                ['sku' => 'SKU-TWS-EARBUDS', 'name' => 'TWS Earbuds', 'quantity' => 28],
                ['sku' => 'SKU-SOUNDBAR-MINI', 'name' => 'Soundbar Mini', 'quantity' => 12],
            ],
            'Komputer' => [
                ['sku' => 'SKU-LAPTOP-ASUS', 'name' => 'Laptop ASUS VivoBook', 'quantity' => 12],
                ['sku' => 'SKU-LAPTOP-LENOVO', 'name' => 'Laptop Lenovo IdeaPad', 'quantity' => 7],
                ['sku' => 'SKU-MONITOR-24', 'name' => 'Monitor LED 24 Inch', 'quantity' => 8],
                ['sku' => 'SKU-KEYBOARD-MECH', 'name' => 'Keyboard Mechanical', 'quantity' => 15],
                ['sku' => 'SKU-PRINTER-INK', 'name' => 'Printer Inkjet', 'quantity' => 5],
            ],
            'Jaringan' => [
                ['sku' => 'SKU-ROUTER-AX3000', 'name' => 'Router WiFi AX3000', 'quantity' => 11],
                ['sku' => 'SKU-SWITCH-8PORT', 'name' => 'Switch 8 Port', 'quantity' => 9],
                ['sku' => 'SKU-LAN-CAT6', 'name' => 'Kabel LAN Cat6', 'quantity' => 100],
                ['sku' => 'SKU-ACCESS-POINT', 'name' => 'Access Point AC1200', 'quantity' => 6],
                ['sku' => 'SKU-MODEM-4G', 'name' => 'Modem 4G LTE', 'quantity' => 4],
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $name => $description) {
            $categoryIds[$name] = Category::query()
                ->updateOrCreate(['name' => $name], ['description' => $description])
                ->id;
        }

        $locationIds = [];
        foreach ($locations as $code => $name) {
            $locationIds[$code] = Location::query()
                ->updateOrCreate(['code' => $code], ['name' => $name])
                ->id;
        }

        $locationCodes = array_keys($locations);

        foreach ($itemsByCategory as $categoryName => $items) {
            foreach (array_values($items) as $index => $data) {
                $locationCode = $locationCodes[$index];

                $item = Item::query()->updateOrCreate(
                    ['sku' => $data['sku']],
                    [
                        'category_id' => $categoryIds[$categoryName],
                        'name' => $data['name'],
                    ],
                );

                DB::table('item_location_stocks')->updateOrInsert(
                    [
                        'item_id' => $item->id,
                        'location_id' => $locationIds[$locationCode],
                    ],
                    [
                        'quantity' => $data['quantity'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }
}
