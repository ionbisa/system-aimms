<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('po_number');
            }

            if (! Schema::hasColumn('purchase_orders', 'transaction_type')) {
                $table->string('transaction_type')->nullable()->after('transaction_date');
            }

            if (! Schema::hasColumn('purchase_orders', 'category')) {
                $table->string('category')->nullable()->after('transaction_type');
            }

            if (! Schema::hasColumn('purchase_orders', 'description')) {
                $table->text('description')->nullable()->after('category');
            }

            if (! Schema::hasColumn('purchase_orders', 'vendor')) {
                $table->string('vendor')->nullable()->after('description');
            }

            if (! Schema::hasColumn('purchase_orders', 'qty')) {
                $table->decimal('qty', 12, 2)->default(1)->after('vendor');
            }

            if (! Schema::hasColumn('purchase_orders', 'unit')) {
                $table->string('unit', 50)->nullable()->after('qty');
            }

            if (! Schema::hasColumn('purchase_orders', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('unit');
            }

            if (! Schema::hasColumn('purchase_orders', 'status_label')) {
                $table->string('status_label')->nullable()->after('status');
            }

            if (! Schema::hasColumn('purchase_orders', 'photo')) {
                $table->string('photo')->nullable()->after('status_label');
            }
        });

        DB::table('purchase_orders')
            ->whereNull('transaction_date')
            ->update(['transaction_date' => DB::raw('DATE(created_at)')]);

        DB::table('purchase_orders')
            ->whereNull('status_label')
            ->update([
                'status_label' => DB::raw("
                    CASE
                        WHEN status = 'Approved' THEN 'Selesai'
                        WHEN status = 'Rejected' THEN 'Pending'
                        ELSE 'Proses'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach ([
                'photo',
                'status_label',
                'unit_price',
                'unit',
                'qty',
                'vendor',
                'description',
                'category',
                'transaction_type',
                'transaction_date',
            ] as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
