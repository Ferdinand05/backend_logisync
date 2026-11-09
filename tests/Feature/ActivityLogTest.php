<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_transaction_is_logged_with_causer_and_values(): void
    {
        [$user, $location, $item] = $this->context();

        Sanctum::actingAs($user);

        $this->postJson('/api/transactions/in', [
            'sku' => $item->sku,
            'location_id' => $location->id,
            'qty' => 4,
            'reference_no' => 'PO-LOG-1',
        ])->assertCreated();

        $activity = Activity::query()
            ->where('log_name', 'transaksi')
            ->where('event', 'created')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame(Transaction::class, $activity->subject_type);
        $this->assertSame('PO-LOG-1', $activity->attribute_changes['attributes']['reference_no']);
        $this->assertSame(4, $activity->attribute_changes['attributes']['quantity']);
    }

    public function test_item_update_records_old_and_new_values(): void
    {
        [, , $item] = $this->context();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $item->update(['name' => 'Barang Baru']);

        $activity = Activity::query()
            ->where('log_name', 'barang')
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($admin->id, $activity->causer_id);
        $this->assertSame('Barang Baru', $activity->attribute_changes['attributes']['name']);
        $this->assertSame('Barang', $activity->attribute_changes['old']['name']);
    }

    public function test_login_and_logout_are_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'password',
            'role' => 'gudang',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertCreated();

        $login = Activity::query()
            ->where('log_name', 'autentikasi')
            ->where('description', 'login')
            ->firstOrFail();

        $this->assertSame($user->id, $login->causer_id);
        $this->assertSame('phpunit', $login->properties['device_name']);

        $token = $user->createToken('perangkat-lain')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'autentikasi',
            'description' => 'logout',
            'causer_id' => $user->id,
        ]);
    }

    public function test_failed_login_is_logged_without_causer(): void
    {
        User::factory()->create(['email' => 'budi@example.com', 'password' => 'password']);

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'salah',
        ])->assertUnauthorized();

        $activity = Activity::query()
            ->where('log_name', 'autentikasi')
            ->where('description', 'login gagal')
            ->firstOrFail();

        $this->assertNull($activity->causer_id);
        $this->assertSame('budi@example.com', $activity->properties['email']);
    }

    public function test_activity_log_page_lists_records_and_shows_change_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $activity = activity('barang')
            ->causedBy($admin)
            ->event('updated')
            ->withChanges([
                'old' => ['name' => 'Nama Lama'],
                'attributes' => ['name' => 'Nama Baru'],
            ])
            ->log('updated');

        $this->actingAs($admin);

        Livewire::test(ListActivityLogs::class)
            ->assertCanSeeTableRecords([$activity])
            ->mountAction(TestAction::make('view')->table($activity))
            ->assertActionMounted(TestAction::make('view')->table($activity))
            ->assertMountedActionModalSee(['Nama Lama', 'Nama Baru']);
    }

    public function test_clean_command_removes_activities_older_than_configured_days(): void
    {
        $old = activity('barang')->log('lama');
        $fresh = activity('barang')->log('baru');

        DB::table('activity_log')->where('id', $old->id)->update([
            'created_at' => now()->subDays(120),
        ]);

        $this->artisan('activitylog:clean')->assertSuccessful();

        $this->assertDatabaseMissing('activity_log', ['id' => $old->id]);
        $this->assertDatabaseHas('activity_log', ['id' => $fresh->id]);
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
            'sku' => 'SKU-LOG-1',
            'name' => 'Barang',
        ]);

        return [$user, $location, $item];
    }
}
