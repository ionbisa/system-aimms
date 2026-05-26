<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_inbounds', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_inbounds', 'unit')) {
                $table->string('unit')->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_inbounds', function (Blueprint $table) {
            if (Schema::hasColumn('stock_inbounds', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
