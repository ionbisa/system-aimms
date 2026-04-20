<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'ga_seen_at')) {
                $table->timestamp('ga_seen_at')->nullable()->after('finance_seen_at');
            }

            if (! Schema::hasColumn('purchase_orders', 'receipt_note')) {
                $table->text('receipt_note')->nullable()->after('realization_note');
            }

            if (! Schema::hasColumn('purchase_orders', 'receipt_file')) {
                $table->string('receipt_file')->nullable()->after('receipt_note');
            }

            if (! Schema::hasColumn('purchase_orders', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('receipt_file')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach ([
                'completed_by',
                'receipt_file',
                'receipt_note',
                'ga_seen_at',
            ] as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    if ($column === 'completed_by') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
