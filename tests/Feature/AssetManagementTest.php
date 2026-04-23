<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_specification_must_not_exceed_maximum_length(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('assets.create'))
            ->post(route('assets.store'), [
                'asset_code' => '002/AST/MKN-GA/III/2026',
                'name' => 'Mesin Uji Panjang',
                'location' => 'Gudang',
                'specification' => str_repeat('A', Asset::SPECIFICATION_MAX_LENGTH + 1),
                'nopol' => '1',
                'type' => 'Office Assets',
                'status' => 'active',
                'pic' => 'Tester',
            ]);

        $response
            ->assertRedirect(route('assets.create'))
            ->assertSessionHasErrors(['specification']);

        $this->assertDatabaseMissing('assets', [
            'asset_code' => '002/AST/MKN-GA/III/2026',
        ]);
    }

    public function test_authenticated_user_can_store_company_asset_with_long_specification(): void
    {
        $user = User::factory()->create();

        $specification = str_repeat('A', Asset::SPECIFICATION_MAX_LENGTH);

        $response = $this
            ->actingAs($user)
            ->post(route('assets.store'), [
                'asset_code' => '001/AST/MKN-GA/III/2026',
                'name' => 'Compressor Angin 2 hp Multipro VBC 200-1',
                'location' => 'Workshop Kendaraan',
                'specification' => $specification,
                'nopol' => '1',
                'type' => 'Office Assets',
                'status' => 'active',
                'pic' => 'Prasetyo',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('assets.index'));

        $asset = Asset::query()->where('asset_code', '001/AST/MKN-GA/III/2026')->first();

        $this->assertNotNull($asset);
        $this->assertSame($specification, $asset->specification);
    }

    public function test_company_asset_page_shows_grouped_summary_inside_detail_markup(): void
    {
        $user = User::factory()->create();

        Asset::query()->create([
            'asset_code' => 'AST-001',
            'name' => 'Kursi Kantor Ergonomis',
            'location' => 'Ruang Admin',
            'specification' => 'Unit 1',
            'nopol' => 'PCS',
            'type' => 'Office Assets',
            'status' => 'active',
            'pic' => 'Sinta',
        ]);

        Asset::query()->create([
            'asset_code' => 'AST-002',
            'name' => 'Kursi Kantor Ergonomis',
            'location' => 'Ruang Finance',
            'specification' => 'Unit 2',
            'nopol' => 'PCS',
            'type' => 'Office Assets',
            'status' => 'maintenance',
            'pic' => 'Budi',
        ]);

        Asset::query()->create([
            'asset_code' => 'AST-003',
            'name' => 'Kursi Kantor Ergonomis',
            'location' => 'Ruang Admin',
            'specification' => 'Unit 3',
            'nopol' => 'PCS',
            'type' => 'Office Assets',
            'status' => 'active',
            'pic' => 'Sinta',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('assets.index', ['category' => 'company']));

        $response
            ->assertOk()
            ->assertDontSeeText('Ringkasan Barang Company Assets')
            ->assertDontSeeText('Rekap Per Nama Barang')
            ->assertSeeText('Ringkasan Barang Sejenis')
            ->assertSeeText('Kursi Kantor Ergonomis')
            ->assertSeeText('Daftar Penempatan Barang Sejenis')
            ->assertSeeText('3')
            ->assertSeeText('2')
            ->assertSeeText('1')
            ->assertSeeText('Ruang Admin')
            ->assertSeeText('Ruang Finance')
            ->assertSeeText('Sinta')
            ->assertSeeText('Budi');
    }

    public function test_company_asset_summary_report_is_available(): void
    {
        $user = User::factory()->create();

        Asset::query()->create([
            'asset_code' => 'AST-101',
            'name' => 'Meja Meeting Lipat',
            'location' => 'Ruang Meeting A',
            'specification' => 'Unit 1',
            'nopol' => 'PCS',
            'type' => 'Office Assets',
            'status' => 'active',
            'pic' => 'Rina',
        ]);

        Asset::query()->create([
            'asset_code' => 'AST-102',
            'name' => 'Meja Meeting Lipat',
            'location' => 'Ruang Meeting B',
            'specification' => 'Unit 2',
            'nopol' => 'PCS',
            'type' => 'Office Assets',
            'status' => 'maintenance',
            'pic' => 'Doni',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', ['type' => 'company_assets_summary']));

        $response
            ->assertOk()
            ->assertSeeText('Ringkasan Company Assets')
            ->assertSeeText('Meja Meeting Lipat')
            ->assertSeeText('2 total')
            ->assertSeeText('1 active')
            ->assertSeeText('1 maintenance')
            ->assertSeeText('Ruang Meeting A')
            ->assertSeeText('Ruang Meeting B')
            ->assertSeeText('Rina')
            ->assertSeeText('Doni')
            ->assertSeeText('AST-101')
            ->assertSeeText('AST-102');
    }

    public function test_asset_management_report_is_available_for_delivery_cars(): void
    {
        $user = User::factory()->create();

        Asset::query()->create([
            'asset_code' => 'DRV-001',
            'name' => 'Mitsubishi L300',
            'location' => 'Pool Timur',
            'specification' => 'Bak terbuka',
            'nopol' => 'E 1234 AA',
            'type' => 'Delivery Cars',
            'status' => 'active',
            'pic' => 'Hendra',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', ['type' => 'asset_delivery_cars']));

        $response
            ->assertOk()
            ->assertSeeText('Asset Management - Delivery Cars')
            ->assertSeeText('Mitsubishi L300')
            ->assertSeeText('DRV-001')
            ->assertSeeText('Pool Timur')
            ->assertSeeText('Hendra');
    }

    public function test_stock_report_shows_on_stock_items_without_brg_prefix(): void
    {
        $user = User::factory()->create();

        Stock::query()->create([
            'item_code' => 'STK-001',
            'item_name' => 'Bearing 6203',
            'location' => 'Gudang Sparepart',
            'qty' => 12,
            'unit' => 'PCS',
            'status' => 'ready',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', [
                'type' => 'stocks',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('Stock Barang')
            ->assertSeeText('Bearing 6203')
            ->assertSeeText('STK-001')
            ->assertSeeText('Gudang Sparepart');
    }

    public function test_stock_inbound_report_filters_by_selected_period(): void
    {
        $user = User::factory()->create();

        Stock::query()->create([
            'item_code' => 'STK-002',
            'item_name' => 'Filter Oli Mesin',
            'location' => 'Gudang Oli',
            'qty' => 20,
            'unit' => 'PCS',
            'status' => 'ready',
        ]);

        DB::table('stock_inbounds')->insert([
            [
                'item_name' => 'Filter Oli Mesin',
                'qty' => 4,
                'created_at' => '2026-04-10 08:00:00',
                'updated_at' => '2026-04-10 08:00:00',
            ],
            [
                'item_name' => 'Filter Oli Lama',
                'qty' => 2,
                'created_at' => '2026-04-02 08:00:00',
                'updated_at' => '2026-04-02 08:00:00',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', [
                'type' => 'stock_inbounds',
                'start_date' => '2026-04-10',
                'end_date' => '2026-04-10',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('Barang Masuk')
            ->assertSeeText('Filter Oli Mesin')
            ->assertDontSeeText('Filter Oli Lama');
    }

    public function test_stock_outbound_report_filters_by_selected_period(): void
    {
        $user = User::factory()->create();

        Stock::query()->create([
            'item_code' => 'STK-003',
            'item_name' => 'Kabel Ties',
            'location' => 'Gudang Utility',
            'qty' => 50,
            'unit' => 'PCS',
            'status' => 'ready',
        ]);

        DB::table('stock_outbounds')->insert([
            [
                'item_name' => 'Kabel Ties',
                'qty' => 10,
                'unit' => 'PCS',
                'description' => 'Distribusi ke Workshop',
                'created_at' => '2026-04-12 09:30:00',
                'updated_at' => '2026-04-12 09:30:00',
            ],
            [
                'item_name' => 'Lakban Kuning',
                'qty' => 5,
                'unit' => 'Roll',
                'description' => 'Distribusi lama',
                'created_at' => '2026-04-01 09:30:00',
                'updated_at' => '2026-04-01 09:30:00',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', [
                'type' => 'stock_outbounds',
                'start_date' => '2026-04-12',
                'end_date' => '2026-04-12',
            ]));

        $response
            ->assertOk()
            ->assertSeeText('Barang Keluar')
            ->assertSeeText('Kabel Ties')
            ->assertSeeText('Distribusi ke Workshop')
            ->assertDontSeeText('Lakban Kuning');
    }
}
