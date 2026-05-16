<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = [
            [
                'item_code' => 'STK-001',
                'item_name' => 'Sarung Tangan Nitrile',
                'specification' => 'Ukuran all size, warna biru',
                'location' => 'Gudang GA',
                'qty' => 120,
                'unit' => 'PCS',
                'status' => 'ready',
            ],
            [
                'item_code' => 'STK-002',
                'item_name' => 'Masker Disposable',
                'specification' => '3 ply',
                'location' => 'Gudang Produksi',
                'qty' => 8,
                'unit' => 'BOX',
                'status' => 'low_stock',
            ],
            [
                'item_code' => 'STK-003',
                'item_name' => 'Lakban Bening',
                'specification' => '48 mm x 100 m',
                'location' => 'Gudang Packing',
                'qty' => 35,
                'unit' => 'Roll',
                'status' => 'ready',
            ],
            [
                'item_code' => 'STK-004',
                'item_name' => 'Cairan Pembersih Lantai',
                'specification' => 'Botol 1 liter',
                'location' => 'Gudang GA',
                'qty' => 18,
                'unit' => 'Botol',
                'status' => 'ready',
            ],
        ];

        foreach ($stocks as $stock) {
            Stock::updateOrCreate(
                ['item_code' => $stock['item_code']],
                $stock
            );
        }
    }
}
