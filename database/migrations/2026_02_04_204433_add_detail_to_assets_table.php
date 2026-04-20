<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'location')) {
                $table->string('location')->nullable()->after('status');
            }

            if (! Schema::hasColumn('assets', 'last_service')) {
                $table->date('last_service')->nullable()->after('location');
            }

            if (! Schema::hasColumn('assets', 'next_service')) {
                $table->date('next_service')->nullable()->after('last_service');
            }

            if (! Schema::hasColumn('assets', 'specification')) {
                $table->text('specification')->nullable()->after('next_service');
            }

            if (! Schema::hasColumn('assets', 'photo')) {
                $table->string('photo')->nullable()->after('specification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $columns = [];

            foreach (['location', 'last_service', 'next_service', 'specification', 'photo'] as $column) {
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
