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

    public function test_on_stock_items_are_sorted_by_item_code(): void
    {
        $user = User::factory()->create();

        foreach ([
            'BRG/GA-SPL/V/2026/001',
            'BRG/GA-ATK/III/2026/001',
            'BRG/GA-MP/V/2026/001',
            'BRG/GA-HSK/V/2026/001',
        ] as $itemCode) {
            Stock::query()->create([
                'item_code' => $itemCode,
                'item_name' => 'Barang ' . $itemCode,
                'location' => 'Gudang',
                'qty' => 10,
                'unit' => 'PCS',
                'status' => 'ready',
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('stock.index'));

        $response
            ->assertOk()
            ->assertSeeTextInOrder([
                'BRG/GA-ATK/III/2026/001',
                'BRG/GA-HSK/V/2026/001',
                'BRG/GA-MP/V/2026/001',
                'BRG/GA-SPL/V/2026/001',
            ]);
    }

    public function test_purchase_order_report_shows_item_unit_and_unit_price(): void
    {
        $user = User::factory()->create();

        $purchaseOrderId = DB::table('purchase_orders')->insertGetId([
            'po_number' => 'PO-REPORT-001',
            'transaction_date' => '2026-04-15',
            'transaction_type' => 'Pembelian Barang',
            'division' => 'GA',
            'category' => 'Sparepart',
            'description' => 'Pembelian kebutuhan gudang',
            'vendor' => 'Vendor Test',
            'qty' => 0,
            'unit' => null,
            'unit_price' => 0,
            'total_price' => 150000,
            'status' => 'Approved',
            'status_label' => 'Approved',
            'created_at' => '2026-04-15 08:00:00',
            'updated_at' => '2026-04-15 08:00:00',
        ]);

        DB::table('purchase_order_items')->insert([
            'purchase_order_id' => $purchaseOrderId,
            'line_number' => 1,
            'item_name' => 'Filter Mesin',
            'qty' => 3,
            'unit' => 'PCS',
            'estimated_unit_price' => 50000,
            'estimated_total_price' => 150000,
            'created_at' => '2026-04-15 08:00:00',
            'updated_at' => '2026-04-15 08:00:00',
        ]);

        $reportParameters = [
            'type' => 'purchase_orders',
            'start_date' => '2026-04-15',
            'end_date' => '2026-04-15',
        ];

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', $reportParameters));

        $response
            ->assertOk()
            ->assertSeeText('Data Pembelian / Purchase Order')
            ->assertSeeText('Satuan')
            ->assertSeeText('Harga Satuan')
            ->assertSeeText('PCS')
            ->assertSeeText('Rp 50.000');

        $printResponse = $this
            ->actingAs($user)
            ->get(route('reports.print', array_merge(['preset' => 'daily'], $reportParameters)));

        $printResponse
            ->assertOk()
            ->assertSeeText('Satuan')
            ->assertSeeText('Harga Satuan')
            ->assertSeeText('PCS')
            ->assertSeeText('Rp 50.000');

        $exportResponse = $this
            ->actingAs($user)
            ->get(route('reports.export-excel', array_merge(['preset' => 'daily'], $reportParameters)));

        $exportContent = $exportResponse->streamedContent();

        $exportResponse->assertOk();
        $this->assertStringContainsString('satuan', $exportContent);
        $this->assertStringContainsString('harga_satuan', $exportContent);
        $this->assertStringContainsString('PCS', $exportContent);
        $this->assertStringContainsString('Rp 50.000', $exportContent);
    }

    public function test_purchase_order_report_is_sorted_by_po_number(): void
    {
        $user = User::factory()->create();

        DB::table('purchase_orders')->insert([
            [
                'po_number' => '011/BBP/GA/V/2026',
                'transaction_date' => '2026-05-03',
                'transaction_type' => 'Pembelian Barang',
                'division' => 'GA',
                'category' => 'Operasional',
                'description' => 'Pembelian servis',
                'vendor' => 'Vendor A',
                'qty' => 1,
                'unit' => 'Unit',
                'unit_price' => 100000,
                'total_price' => 100000,
                'status' => 'Approved',
                'status_label' => 'Approved',
                'created_at' => '2026-05-03 08:00:00',
                'updated_at' => '2026-05-03 08:00:00',
            ],
            [
                'po_number' => '002/BBP/GA/V/2026',
                'transaction_date' => '2026-05-01',
                'transaction_type' => 'Pembelian Barang',
                'division' => 'GA',
                'category' => 'Operasional',
                'description' => 'Pembelian alat',
                'vendor' => 'Vendor B',
                'qty' => 1,
                'unit' => 'Unit',
                'unit_price' => 200000,
                'total_price' => 200000,
                'status' => 'Approved',
                'status_label' => 'Approved',
                'created_at' => '2026-05-01 08:00:00',
                'updated_at' => '2026-05-01 08:00:00',
            ],
            [
                'po_number' => '005/BBP/GA/V/2026',
                'transaction_date' => '2026-05-02',
                'transaction_type' => 'Pembelian Barang',
                'division' => 'GA',
                'category' => 'Operasional',
                'description' => 'Pembelian bahan',
                'vendor' => 'Vendor C',
                'qty' => 1,
                'unit' => 'Unit',
                'unit_price' => 150000,
                'total_price' => 150000,
                'status' => 'Approved',
                'status_label' => 'Approved',
                'created_at' => '2026-05-02 08:00:00',
                'updated_at' => '2026-05-02 08:00:00',
            ],
        ]);

        $reportParameters = [
            'type' => 'purchase_orders',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-03',
        ];

        $response = $this
            ->actingAs($user)
            ->get(route('reports.daily', $reportParameters));

        $response
            ->assertOk()
            ->assertSeeTextInOrder([
                '002/BBP/GA/V/2026',
                '005/BBP/GA/V/2026',
                '011/BBP/GA/V/2026',
            ]);
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
            ->assertSeeText('Satuan')
            ->assertSeeText('Filter Oli Mesin')
            ->assertSeeText('PCS')
            ->assertDontSeeText('Filter Oli Lama');

        $printResponse = $this
            ->actingAs($user)
            ->get(route('reports.print', [
                'preset' => 'daily',
                'type' => 'stock_inbounds',
                'start_date' => '2026-04-10',
                'end_date' => '2026-04-10',
            ]));

        $printResponse
            ->assertOk()
            ->assertSeeText('Satuan')
            ->assertSeeText('PCS');

        $exportResponse = $this
            ->actingAs($user)
            ->get(route('reports.export-excel', [
                'preset' => 'daily',
                'type' => 'stock_inbounds',
                'start_date' => '2026-04-10',
                'end_date' => '2026-04-10',
            ]));

        $exportContent = $exportResponse->streamedContent();

        $exportResponse->assertOk();
        $this->assertStringContainsString('satuan', $exportContent);
        $this->assertStringContainsString('PCS', $exportContent);
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
