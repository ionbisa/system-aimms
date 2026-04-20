<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('category');
            }

            if (! Schema::hasColumn('assets', 'value')) {
                $table->decimal('value', 15, 2)->default(0)->after('purchase_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $columns = [];

            foreach (['purchase_date', 'value'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
