<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('item_request_items', 'procurement_type')) {
                $table->string('procurement_type')->default('stock_distribution')->after('distributed_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('item_request_items', 'procurement_type')) {
                $table->dropColumn('procurement_type');
            }
        });
    }
};
