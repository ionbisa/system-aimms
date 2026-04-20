<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {

            // TAMBAH asset_id
            $table->foreignId('asset_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('assets')
                  ->onDelete('cascade');

            // GANTI qty (tetap)
            // item_name TIDAK DIPAKAI LAGI
            $table->dropColumn('item_name');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {

            // BALIKIN JIKA ROLLBACK
            $table->string('item_name')->after('id');
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
        });
    }
};
