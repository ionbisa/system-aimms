<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_outbounds', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_outbounds', 'unit')) {
                $table->string('unit')->nullable()->after('qty');
            }

            if (! Schema::hasColumn('stock_outbounds', 'description')) {
                $table->text('description')->nullable()->after('unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_outbounds', function (Blueprint $table) {
            if (Schema::hasColumn('stock_outbounds', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('stock_outbounds', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
