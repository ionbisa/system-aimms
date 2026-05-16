<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemRequestStockSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_shows_on_stock_items_without_brg_prefix(): void
    {
        $role = Role::query()->create(['name' => 'Admin Produksi']);
        $user = User::factory()->create();
        $user->assignRole($role);

        Stock::query()->create([
            'item_code' => 'STK-001',
            'item_name' => 'Sarung Tangan Nitrile',
            'qty' => 120,
            'unit' => 'PCS',
            'status' => 'ready',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('item-requests.create'));

        $response
            ->assertOk()
            ->assertSeeText('Pilih dari On Stock')
            ->assertSeeText('STK-001 - Sarung Tangan Nitrile');
    }

    public function test_create_form_handles_stock_names_with_javascript_sensitive_characters(): void
    {
        $role = Role::query()->create(['name' => 'Admin Produksi']);
        $user = User::factory()->create();
        $user->assignRole($role);

        Stock::query()->create([
            'item_code' => 'STK-002',
            'item_name' => 'Lakban `Bening` "2 inch"',
            'qty' => 35,
            'unit' => 'Roll',
            'status' => 'ready',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('item-requests.create'));

        $response
            ->assertOk()
            ->assertSeeText('STK-002 - Lakban `Bening` "2 inch"');
    }

    public function test_create_form_shows_stock_even_when_linked_to_asset(): void
    {
        $role = Role::query()->create(['name' => 'Admin Produksi']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $asset = Asset::query()->create([
            'asset_code' => 'AST-001',
            'name' => 'Rak Gudang',
            'type' => 'Office Assets',
            'status' => 'active',
        ]);

        Stock::query()->create([
            'asset_id' => $asset->id,
            'item_code' => 'STK-003',
            'item_name' => 'Tinta Printer',
            'qty' => 12,
            'unit' => 'PCS',
            'status' => 'ready',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('item-requests.create'));

        $response
            ->assertOk()
            ->assertSeeText('STK-003 - Tinta Printer');
    }
}
