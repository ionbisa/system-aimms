<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            [
                'asset_code' => 'AST-001',
                'name' => 'Laptop Operasional',
                'category' => 'Office',
                'purchase_date' => now()->subYear(),
                'value' => 15000000,
                'status' => 'active',
            ],
            [
                'asset_code' => 'AST-002',
                'name' => 'Printer Kantor',
                'category' => 'Office',
                'purchase_date' => now()->subMonths(8),
                'value' => 5000000,
                'status' => 'maintenance',
            ],
            [
                'asset_code' => 'AST-003',
                'name' => 'Motor Operasional',
                'category' => 'Motor',
                'purchase_date' => now()->subYears(2),
                'value' => 18000000,
                'status' => 'active',
            ],
        ];

        foreach ($assets as $asset) {
            Asset::create($asset);
        }
    }
}
