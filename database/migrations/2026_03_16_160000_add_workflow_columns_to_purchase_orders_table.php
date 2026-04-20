<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'requested_by')) {
                $table->foreignId('requested_by')->nullable()->after('division')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_orders', 'requested_role')) {
                $table->string('requested_role')->nullable()->after('requested_by');
            }

            if (! Schema::hasColumn('purchase_orders', 'overall_status')) {
                $table->string('overall_status')->default('pending')->after('status_label');
            }

            if (! Schema::hasColumn('purchase_orders', 'current_step')) {
                $table->string('current_step')->default('waiting_operational_manager')->after('overall_status');
            }

            if (! Schema::hasColumn('purchase_orders', 'initial_note')) {
                $table->text('initial_note')->nullable()->after('current_step');
            }

            if (! Schema::hasColumn('purchase_orders', 'final_approved_at')) {
                $table->timestamp('final_approved_at')->nullable()->after('initial_note');
            }

            if (! Schema::hasColumn('purchase_orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('final_approved_at');
            }

            if (! Schema::hasColumn('purchase_orders', 'expired_at')) {
                $table->timestamp('expired_at')->nullable()->after('rejected_at');
            }

            if (! Schema::hasColumn('purchase_orders', 'finance_seen_at')) {
                $table->timestamp('finance_seen_at')->nullable()->after('expired_at');
            }

            if (! Schema::hasColumn('purchase_orders', 'realization_status')) {
                $table->string('realization_status')->nullable()->after('finance_seen_at');
            }

            if (! Schema::hasColumn('purchase_orders', 'realization_note')) {
                $table->text('realization_note')->nullable()->after('realization_status');
            }

            if (! Schema::hasColumn('purchase_orders', 'realized_by')) {
                $table->foreignId('realized_by')->nullable()->after('realization_note')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_orders', 'realized_at')) {
                $table->timestamp('realized_at')->nullable()->after('realized_by');
            }

            if (! Schema::hasColumn('purchase_orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('realized_at');
            }

            if (! Schema::hasColumn('purchase_orders', 'last_action_at')) {
                $table->timestamp('last_action_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach ([
                'last_action_at',
                'completed_at',
                'realized_at',
                'realized_by',
                'realization_note',
                'realization_status',
                'finance_seen_at',
                'expired_at',
                'rejected_at',
                'final_approved_at',
                'initial_note',
                'current_step',
                'overall_status',
                'requested_role',
                'requested_by',
            ] as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    if (in_array($column, ['requested_by', 'realized_by'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
