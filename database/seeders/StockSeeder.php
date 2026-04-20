<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stock;
use App\Models\Asset;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $assets = Asset::all();

        foreach ($assets as $asset) {
            Stock::create([
                'asset_id' => $asset->id,
                'qty' => rand(5, 50),
            ]);
        }
    }
}
