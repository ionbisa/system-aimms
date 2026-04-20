<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('stocks', 'item_code')) {
                $table->string('item_code')->nullable()->after('asset_id');
            }

            if (! Schema::hasColumn('stocks', 'item_name')) {
                $table->string('item_name')->nullable()->after('item_code');
            }

            if (! Schema::hasColumn('stocks', 'specification')) {
                $table->text('specification')->nullable()->after('item_name');
            }

            if (! Schema::hasColumn('stocks', 'location')) {
                $table->string('location')->nullable()->after('specification');
            }

            if (! Schema::hasColumn('stocks', 'unit')) {
                $table->string('unit')->nullable()->after('qty');
            }

            if (! Schema::hasColumn('stocks', 'status')) {
                $table->string('status')->nullable()->after('unit');
            }

            if (! Schema::hasColumn('stocks', 'photo')) {
                $table->string('photo')->nullable()->after('status');
            }
        });

        if (Schema::hasTable('assets') && Schema::hasColumn('stocks', 'asset_id')) {
            $assetCodeSelect = Schema::hasColumn('assets', 'asset_code')
                ? 'assets.asset_code'
                : 'NULL as asset_code';
            $assetNameSelect = Schema::hasColumn('assets', 'name')
                ? 'assets.name'
                : 'NULL as name';
            $assetSpecificationSelect = Schema::hasColumn('assets', 'specification')
                ? 'assets.specification as asset_specification'
                : 'NULL as asset_specification';
            $assetLocationSelect = Schema::hasColumn('assets', 'location')
                ? 'assets.location as asset_location'
                : 'NULL as asset_location';
            $assetUnitSelect = Schema::hasColumn('assets', 'nopol')
                ? 'assets.nopol'
                : 'NULL as nopol';
            $assetStatusSelect = Schema::hasColumn('assets', 'status')
                ? 'assets.status as asset_status'
                : 'NULL as asset_status';
            $assetPhotoSelect = Schema::hasColumn('assets', 'photo')
                ? 'assets.photo as asset_photo'
                : 'NULL as asset_photo';

            DB::table('stocks')
                ->leftJoin('assets', 'assets.id', '=', 'stocks.asset_id')
                ->select(
                    'stocks.id',
                    'stocks.item_code',
                    'stocks.item_name',
                    'stocks.specification',
                    'stocks.location',
                    'stocks.unit',
                    'stocks.status',
                    'stocks.photo',
                    DB::raw($assetCodeSelect),
                    DB::raw($assetNameSelect),
                    DB::raw($assetSpecificationSelect),
                    DB::raw($assetLocationSelect),
                    DB::raw($assetUnitSelect),
                    DB::raw($assetStatusSelect),
                    DB::raw($assetPhotoSelect)
                )
                ->orderBy('stocks.id')
                ->lazy()
                ->each(function ($stock) {
                    DB::table('stocks')
                        ->where('id', $stock->id)
                        ->update([
                            'item_code' => $stock->item_code ?? $stock->asset_code,
                            'item_name' => $stock->item_name ?? $stock->name,
                            'specification' => $stock->specification ?? $stock->asset_specification,
                            'location' => $stock->location ?? $stock->asset_location,
                            'unit' => $stock->unit ?? $stock->nopol,
                            'status' => $stock->status ?? $stock->asset_status,
                            'photo' => $stock->photo ?? $stock->asset_photo,
                        ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $columns = [];

            foreach (['item_code', 'item_name', 'specification', 'location', 'unit', 'status', 'photo'] as $column) {
                if (Schema::hasColumn('stocks', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
