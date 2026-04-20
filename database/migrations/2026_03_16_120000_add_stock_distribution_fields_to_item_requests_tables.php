<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('item_requests', 'stock_deducted_at')) {
                $table->timestamp('stock_deducted_at')->nullable()->after('completed_at');
            }
        });

        Schema::table('item_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('item_request_items', 'stock_id')) {
                $table->foreignId('stock_id')->nullable()->after('description')->constrained('stocks')->nullOnDelete();
            }

            if (! Schema::hasColumn('item_request_items', 'distributed_qty')) {
                $table->decimal('distributed_qty', 12, 2)->default(0)->after('stock_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('item_request_items', 'stock_id')) {
                $table->dropConstrainedForeignId('stock_id');
            }

            if (Schema::hasColumn('item_request_items', 'distributed_qty')) {
                $table->dropColumn('distributed_qty');
            }
        });

        Schema::table('item_requests', function (Blueprint $table) {
            if (Schema::hasColumn('item_requests', 'stock_deducted_at')) {
                $table->dropColumn('stock_deducted_at');
            }
        });
    }
};
