<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\User;
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
}
